<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;

/**
  * 车型详情
  * @author xu
  * @copyright 2018/01/29
  */
class fourstep{

	// model_detail
	public static function model_detail()
	{
		// 下载
		Capsule::table('model_list')->where('status','wait')->orderBy('id')->chunk(10,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'model_list', 0777, true);
			// 并发请求
		    $guzzle = new guzzle();
		    $guzzle->poolRequest('model_list',$datas);
		});

		// 解析
		Capsule::table('model_list')->where('status','completed')->orderBy('id')->chunk(10,function($datas){
			// 站点路径
			$prefix = 'https://car.autohome.com.cn';
			// 配置路径
			$detailPrefix = 'https://car.autohome.com.cn/config/spec/';
			// 循环
		    foreach ($datas as $data)
		    {
		    	$file = PROJECT_APP_DOWN.'model_list/'.$data->id.'.html';
		    	if (file_exists($file))
		    	{
					$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");
		    		if($dom = HtmlDomParser::str_get_html($html))
					{
						// 下一页
						if($dom->find('.page-item-next',0))
						{
							if($dom->find('.page-item-next',0)->href != 'javascript:void(0)')
							{
								$list_url = $prefix.$dom->find('.page-item-next',0)->href;
							    $temp = [
							    	'url' => $list_url,
							    	'status' => 'wait',
							    	'md5_url' => md5($list_url),
							    	'brand' => $data->brand,
							    	'subbrand' => $data->subbrand,
							    	'series' => $data->series,
							    	'onsell' => $data->onsell
							    ];
								$empty = Capsule::table('model_list')->where('md5_url',md5($list_url))->get()->isEmpty();
								if($empty) Capsule::table('model_list')->insert($temp);
							}
						}
						// 配置链接												
						foreach ($dom->find('.interval01-list li .interval01-list-cars-infor') as $div)
						{
							preg_match('/\/spec\/(\d+)\//',ltrim($div->find('p',0)->find('a',0)->href,'//'),$match);
							$url = $detailPrefix.$match[1].'.html';
							$model = $div->find('p',0)->find('a',0)->plaintext;
						    $temp = [
						    	'url' => $url,
						    	'status' => 'wait',
						    	'md5_url' => md5($url),
						    	'brand' => $data->brand,
						    	'subbrand' => $data->subbrand,
						    	'series' => $data->series,
						    	'model' => $model,
						    	'onsell' => $data->onsell
						    ];
							$empty = Capsule::table('model_detail')->where('md5_url',md5($url))->get()->isEmpty();
							if($empty) Capsule::table('model_detail')->insert($temp);
						}
			            // 标记已读
			            Capsule::table('model_list')->where('id', $data->id)->update(['status' =>'readed']);
					    // 结果
			            echo 'model_list '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});
		// 剩余统计
		$wait = Capsule::table('model_list')->where('status', 'wait')->count();
        if($wait) echo "still have item of model_list need to download ,sum : ".$wait.PHP_EOL;
	}

}
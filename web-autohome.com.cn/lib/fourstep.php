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
		// 下载所有的model页面
		Capsule::table('model_list')->where('status','wait')->orderBy('id')->chunk(10,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'model_list', 0777, true);
			// 并发请求
		    $guzzle = new guzzle();
		    $guzzle->poolRequest('model_list',$datas);
		});

		// 解析所有的model页面获取engine信息
		Capsule::table('model_list')->where('status','completed')->orderBy('id')->chunk(10,function($datas){
			// 站点根路径
			$prefix = 'https://car.autohome.com.cn';
			// 详情配置页面根路径
			$detailPrefix = 'https://car.autohome.com.cn/config/spec/';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	$file = PROJECT_APP_DOWN.'model_list/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		// 字符编码转换
					$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");

		    		if($dom = HtmlDomParser::str_get_html($html))
					{
						// 检测是否有下一页
						if($dom->find('.page-item-next',0))
						{
							if($dom->find('.page-item-next',0)->href != 'javascript:void(0)')
							{
								$list_url = $prefix.$dom->find('.page-item-next',0)->href;
								// 存储
							    $temp = [
							    	'url' => $list_url,
							    	'status' => 'wait',
							    	'md5_url' => md5($list_url),
							    	'brand' => $data->brand,
							    	'subbrand' => $data->subbrand,
							    	'series' => $data->series
							    ];
							    // 入库下一页
								$empty = Capsule::table('model_list')
									->where('md5_url',md5($list_url))
									->get()
									->isEmpty();
								if($empty)
								{
									Capsule::table('model_list')->insert($temp);					    	
								}
							}
						}
						// 车型入库												
						foreach ($dom->find('.interval01-list li .interval01-list-cars-infor') as $div)
						{
							
							// 正则匹配获取spec的ID
							preg_match('/\/spec\/(\d+)\//',ltrim($div->find('p',0)->find('a',0)->href,'//'),$match);

							$url = $detailPrefix.$match[1].'.html';
							$model = $div->find('p',0)->find('a',0)->plaintext;

							// 存储
						    $temp = [
						    	'url' => $url,
						    	'status' => 'wait',
						    	'md5_url' => md5($url),
						    	'brand' => $data->brand,
						    	'subbrand' => $data->subbrand,
						    	'series' => $data->series,
						    	'model' => $model
						    ];
						    // 入库详情表
							$empty = Capsule::table('model_detail')
								->where('md5_url',md5($url))
								->get()
								->isEmpty();
							if($empty)
							{
								Capsule::table('model_detail')->insert($temp);					    	
							}
						}
			            // 更改SQL语句
			            Capsule::table('model_list')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'model_list '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});

		// 获取需要下载的页面
		$wait = Capsule::table('model_list')
            ->where('status', 'wait')
           	->count();
        if($wait) echo "still have item of model_list need to download ,sum : ".$wait."\r\n";
	}

}
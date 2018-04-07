<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;


use Illuminate\Database\Schema\Blueprint;

/**
  * @author xu
  * @copyright 2018/01/29
  */
class threestep{

	// 下载
	public static function model_list()
	{

		// 下载
		$guzzle = new guzzle();
		$empty = Capsule::table('series')->where('status','wait')->get()->isEmpty();
		// 创建文件夹
		@mkdir(PROJECT_APP_DOWN.'series', 0777, true);
		while(!$empty) {
			$datas = Capsule::table('series')->where('status','wait')->orderBy('id')->limit(10)->get();
			// 并发请求
		    $guzzle->poolRequest('series',$datas);
		    // 是否完成
		    $empty = Capsule::table('series')->where('status','wait')->get()->isEmpty();
		}

		// 解析
		Capsule::table('series')->where('status','completed')->orderBy('id')->chunk(20,function($datas){
			$prefix = 'https://car.autohome.com.cn';
		    foreach ($datas as $data)
		    {
		    	$file = PROJECT_APP_DOWN.'series/'.$data->id.'.html';
		    	if (file_exists($file))
		    	{
		    		// 字符编码转换
					$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");
		    		if($dom = HtmlDomParser::str_get_html($html))
					{
						// 获取在售、停售、即将销售的url
						foreach ($dom->find(".border-t-no ul li") as $li)
						{
							// 过滤无效连接
							if(!$li->find('a',0)) continue;
							// 获取所有链接
							$url = $prefix.$li->find('a',0)->href;
							// 存储
						    $temp = [
						    	'url' => $url,
						    	'status' => 'wait',
						    	'md5_url' => md5($url),
						    	'brand' => $data->brand,
						    	'subbrand' => $data->subbrand,
						    	'series' => $data->series,
						    	'onsell' => $li->find('a',0)->plaintext
						    ];
						    // 入库
							$empty = Capsule::table('model_list')->where('md5_url',md5($url))->get()->isEmpty();
							if($empty) Capsule::table('model_list')->insert($temp);
						}
			            // 标记已读
			            Capsule::table('series')->where('id', $data->id)->update(['status' =>'readed']);
			            echo 'series '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});
	}
}
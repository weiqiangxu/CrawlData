<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;

/**
  * 下载所有零件详情页面
  * @author xu
  * @copyright 2018/01/29
  */
class fourstep{

	// engine
	public static function engine()
	{
		// 下载所有的model页面
		Capsule::table('model')->where('status','wait')->orderBy('id')->chunk(10,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'model', 0777, true);
			// 并发请求
		    $guzzle = new guzzle();
		    $guzzle->poolRequest('model',$datas);
		    sleep(2);
		});

		// 解析所有的model页面获取engine信息
		Capsule::table('model')->where('status','completed')->orderBy('id')->chunk(10,function($datas){

			$prefix = 'http://www.rockauto.com';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	$file = PROJECT_APP_DOWN.'model/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		if($dom = HtmlDomParser::str_get_html(file_get_contents($file)))
					{
						// 获取brand页面所有的model
						foreach($dom->find('.nchildren .nchildren .nchildren .navlabellink') as $li)
						{
							$url = $prefix.$li->href;
						    // 存储
						    $temp = [
						    	'url' => $url,
						    	'status' => 'wait',
						    	'md5_url' => md5($url),
						    	'status' => 'wait',
						    	// 想要的数据
								'brand' => $data->brand,
								'year' => $data->year,
								'model' => $data->model,
								'engine' => $li->plaintext
						    ];
						    $empty = Capsule::table('engine')
						    	->where('md5_url',md5($url))
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
							    Capsule::table('engine')->insert($temp);					    	
						    }
						}
			            // 更改SQL语句
			            Capsule::table('model')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'model '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});


	}

}
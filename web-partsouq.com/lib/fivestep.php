<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;


use Illuminate\Database\Schema\Blueprint;

/**
  * 下载所有零件详情页面
  * @author xu
  * @copyright 2018/01/29
  */
class fivestep{



	// 零件 =》 图片详情
	public static function pic_url()
	{
		// 下载所有的part页面
		Capsule::table('url_part')->where('status','wait')->orderBy('id')->chunk(5,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'url_part', 0777, true);
		    // 并发请求
		    $guzzle = new guzzle();
		    $guzzle->poolRequest('url_part',$datas);

		});
		// 获取所有的图片连接（最后一级别啦）
		Capsule::table('url_part')->where('status','completed')->orderBy('id')->chunk(5,function($datas){
			$prefix ='https://partsouq.com';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	$file = PROJECT_APP_DOWN.'url_part/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 获取pic详情页url
						foreach($dom->find('.caption a') as $a)
						{
						    // 存储进去所有的part
						    $temp = [
						    	'url' => $prefix.$a->href,
						    	'status' => 'wait',
						    	'md5_url' => md5($prefix.$a->href),
						    	'car_id' => $data->car_id
						    ];
						    $empty = Capsule::table('url_pic')
						    	->where('md5_url', md5($prefix.$a->href))
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
							    Capsule::table('url_pic')->insert($temp);					    	
						    }
						}
			            // 更改SQL语句
			            Capsule::table('url_part')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'url_part '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});
	}

}
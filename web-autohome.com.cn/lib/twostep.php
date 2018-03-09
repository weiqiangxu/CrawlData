<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;

/**
  * @author xu
  * @copyright 2018/01/29
  */
class twostep{

	// 车系=》车
	public static function brand_two()
	{
		// 下载所有的brand页面
		Capsule::table('brand_one')->where('status','wait')->orderBy('id')->chunk(10,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'brand_one', 0777, true);
			// 并发请求
		    $guzzle = new guzzle();
		    $guzzle->poolRequest('brand_one',$datas);	    
		});

		// 获取所有的year连接
		Capsule::table('brand_one')->where('status','completed')->orderBy('id')->chunk(20,function($datas){

			$prefix = 'https://car.autohome.com.cn';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	$file = PROJECT_APP_DOWN.'brand_one/'.$data->id.'.html';


		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		// 字符编码转换
		    		$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");

		    		if($dom = HtmlDomParser::str_get_html($html))
					{
						// 获取brand页面所有的model
						foreach($dom->find('#cartree .current dd a') as $a)
						{
							$url = $prefix.$a->href;
						    // 存储进去所有的&model
						    $temp = [
						    	'url' => $url,
						    	'status' => 'wait',
						    	'md5_url' => md5($url),
						    	'status' => 'wait',
						    	'brand_one' => $data->brand_one,
						    	'brand_two' => $a->plaintext
						    ];

						    var_dump($temp);die;

						    $empty = Capsule::table('year')
						    	->where('md5_url',md5($url))
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
							    Capsule::table('year')->insert($temp);					    	
						    }
						}
						die;
			            // 更改SQL语句
			            Capsule::table('brand_one')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'brand_one '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});

	}
}
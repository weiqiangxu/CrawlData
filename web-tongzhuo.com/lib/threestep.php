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
class threestep{

	// 获取年份下面的model连接
	public static function model()
	{
		// year
		Capsule::table('year')->where('status','wait')->orderBy('id')->chunk(10,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'year', 0777, true);
			// 并发请求
		    $guzzle = new guzzle();
		    $guzzle->poolRequest('year',$datas);
		    sleep(2);    
		});


		// 获取所有的year连接
		Capsule::table('year')->where('status','completed')->orderBy('id')->chunk(20,function($datas){

			$prefix = 'http://www.rockauto.com';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	$file = PROJECT_APP_DOWN.'year/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		if($dom = HtmlDomParser::str_get_html(file_get_contents($file)))
					{
						// 获取brand页面所有的model
						foreach($dom->find('.nchildren .nchildren .navlabellink') as $li)
						{
							$url = $prefix.$li->href;
						    // 存储进去所有的&model
						    $temp = [
						    	'url' => $url,
						    	'status' => 'wait',
						    	'md5_url' => md5($url),
						    	'status' => 'wait',
						    	'brand' => $data->brand,
						    	'year' => $data->year,
						    	'model' => $li->plaintext
						    ];
						    $empty = Capsule::table('model')
						    	->where('md5_url',md5($url))
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
							    Capsule::table('model')->insert($temp);					    	
						    }
						}
			            // 更改SQL语句
			            Capsule::table('year')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'year '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});

		// 检测是否还有未下载完成
		$wait = Capsule::table('year')
            ->where('status', 'wait')
           	->count();
        if($wait>0) echo "still have item need to download ,sum : ".$wait."\r\n";

	}
}
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

	// 所有的url_market都已经加上筛选条件获取的，此刻url_market的下载链接页面完整，含有所有car的页面
	public static function car()
	{
		// 下载所有的model页面
		Capsule::table('url_market')->where('status','last')->orderBy('id')->chunk(20,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'url_market', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	$guzzle = new guzzle();
		    	$guzzle->down('url_market',$data);
		    }
		});
		// 获取所有的车连接
		Capsule::table('url_market')->where('status','completed')->orderBy('id')->chunk(20,function($datas){
			$prefix ='https://partsouq.com';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	// 保存文件名
		    	$file = PROJECT_APP_DOWN.'url_market/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 获取brand页面所有的model
						foreach($dom->find('.search-result-vin tr') as $tr)
						{
							if(!$tr->find("a",0))
							{
								continue;
							}
						    // 存储进去所有的&url_car
						    $temp = [
						    	'url' => html_entity_decode($prefix.$tr->find("a",0)->href),
						    	'md5_url' => md5($prefix.$tr->find("a",0)->href),
						    	'status' => 'wait',
						    ];
						    $empty = Capsule::table('url_car')
						    	->where('md5_url',md5($prefix.$tr->find("a",0)->href))
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
							    Capsule::table('url_car')->insert($temp);					    	
						    }
						}
			            // 更改SQL语句
			            Capsule::table('url_market')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'url_market '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});
	}
}
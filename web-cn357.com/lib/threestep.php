<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;


/**
  * 下载所有需要解析的详情页
  * @author xu
  * @copyright 2018/01/24
  */
class threestep{

	// 初始化列表页
	public static function download()
	{
		// chunk分块处理每100条数据
		Capsule::table('url_detail')->where('status','wait')->orderBy('id')->chunk(100,function($datas){
			// 创建文件夹
    		@mkdir(PROJECT_APP_DOWN.'url_detail', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	$guzzle = new guzzle();
		    	$guzzle->down('url_detail',$data);
		    }
		});
	}	
}
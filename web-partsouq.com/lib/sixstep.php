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
class sixstep{

	public static function pic_down()
	{
		// 下载所有的配件详情页面
		Capsule::table('url_pic')->where('status','wait')->orderBy('id')->chunk(5,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'url_pic', 0777, true);
		    // 调用guzzle实现并发异步请求下载
		    $guzzle = new guzzle();
		    $guzzle->poolRequest('url_pic',$datas);
		});
	}
}
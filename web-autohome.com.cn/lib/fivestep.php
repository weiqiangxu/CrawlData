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
class fivestep{


	// 下载
	public static function car_down()
	{
		// 下载所有的model页面
		Capsule::table('model_detail')->where('status','wait')->orderBy('id')->chunk(10,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'model_detail', 0777, true);
			// 并发请求
		    $guzzle = new guzzle();
		    $guzzle->poolRequest('model_detail',$datas);
		});
	}
	// 分析
	public static function car_analyse()
	{
		Capsule::table('model_detail')->where('status','completed')->orderBy('id')->chunk(1,function($datas){
			$phan = array();
			$start = time();
			foreach ($datas as $data)
			{
				$phan[] = new phan($data);
			}
			// 开始线程(调用start就会运行run方法)
			foreach($phan as $v) {
			    $v->start();
			}
			// 各个线程异步执行
			foreach($phan as $v) {
			    $v->join();
			}
			echo 'time: '.(time()-$start) .' second. '.PHP_EOL;
		});
	}
}
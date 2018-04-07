<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;

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
		$guzzle = new guzzle();
		$empty = Capsule::table('model_detail')->where('status','wait')->get()->isEmpty();
		// 创建文件夹
		@mkdir(PROJECT_APP_DOWN.'model_detail', 0777, true);
		while(!$empty) {
			$datas = Capsule::table('model_detail')->where('status','wait')->orderBy('id')->limit(10)->get();
			// 并发请求
		    $guzzle->poolRequest('model_detail',$datas);
		    // 是否完成
		    $empty = Capsule::table('model_detail')->where('status','wait')->get()->isEmpty();
		}
	}
	// 分析
	public static function car_analyse()
	{

		$empty = Capsule::table('model_detail')->where('status','completed')->get()->isEmpty();
		while(!$empty) {
			// 取出10条
			$datas = Capsule::table('model_detail')->where('status','completed')->orderBy('id')->limit(20)->get();
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
			$empty = Capsule::table('model_detail')->where('status','completed')->get()->isEmpty();
		}
	}
}
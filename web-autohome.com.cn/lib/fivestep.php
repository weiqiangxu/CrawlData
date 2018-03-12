<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;
use JonnyW\PhantomJs\Client;

/**
  * 车型详情
  * @author xu
  * @copyright 2018/01/29
  */
class fivestep{

	// car
	public static function car()
	{

		// 解析JavaScript渲染后的页面
		Capsule::table('model_detail')->where('status','completed')->orderBy('id')->chunk(10,function($datas){
			// 循环块级结果
		    foreach ($datas as $data)
		    {
				$client = Client::getInstance();

				$client->getEngine()->setPath(APP_PATH.'/bin/phantomjs.exe');
				$request  = $client->getMessageFactory()->createRequest();
				$response = $client->getMessageFactory()->createResponse();

				$request->setMethod('GET');
				$request->setUrl($data->url);

				$client->send($request, $response);

				if($response->getStatus() === 200) {
				    
		    		if($dom = HtmlDomParser::str_get_html($response->getContent()))
					{
						var_dump($dom->find("#tr_9",0)->children(1)->outertext);die;
					}
				}
		    }
		});




		// 下载所有的model_detail页面
		Capsule::table('model_detail')->where('status','wait')->orderBy('id')->chunk(10,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'model_detail', 0777, true);
			// 并发请求
		    $guzzle = new guzzle();
		    $guzzle->poolRequest('model_detail',$datas);
		});


		// 解析所有的model_detail获取所有车的数据入库
		Capsule::table('model_detail')->where('status','completed')->orderBy('id')->chunk(10,function($datas){
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	$file = PROJECT_APP_DOWN.'model_detail/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		// 字符编码转换
					$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");

		    		if($dom = HtmlDomParser::str_get_html($html))
					{
			            // 更改SQL语句
			            Capsule::table('model_detail')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'model_detail '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});


		// 获取需要下载的页面
		$wait = Capsule::table('model_detail')
            ->where('status', 'wait')
           	->count();
        if($wait) echo "still have item of model_detail need to download ,sum : ".$wait."\r\n";
	}

}
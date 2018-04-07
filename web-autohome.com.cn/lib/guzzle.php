<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;


use Illuminate\Database\Schema\Blueprint;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

use GuzzleHttp\Pool;

use JonnyW\PhantomJs\Client as PhantomJsClitent;


class guzzle{

	// 按顺序处理单个异步请求
	public function down($step,$data)
	{
		// 页面文件名
    	$file = PROJECT_APP_DOWN.$step.'/'.$data->id.'.html';
		$client = new Client();

		$config = array(
			// 'proxy'=>'https://110.82.102.109:34098'
		);
		// 注册异步请求
		$client->getAsync(html_entity_decode(unicode_decode($data->url)),$config)->then(
			// 成功获取页面回调
		    function (ResponseInterface $res) use ($step,$file,$data)
		    {
				if($res->getStatusCode()== 200)
	    		{
	    			// 保存文件
		            file_put_contents($file,$res->getBody());
		            // 命令行执行时候不需要经过apache直接输出在窗口
		            echo $step.' '.$data->id.'.html'." download successful!".PHP_EOL;
	    		}
	    		if(file_exists($file))
		    	{
		            // 更改SQL语句
		            Capsule::table($step)
				            ->where('id', $data->id)
				            ->update(['status' =>'completed']);
		    	}
		    },
		    // 请求失败回调
		    function (RequestException $e) {
		        echo $e->getMessage().PHP_EOL;
		        echo $e->getRequest()->getMethod().PHP_EOL;
		    }
		)->wait();
	}

	// 并发处理多个-协程就是用户态的线程-运用协程实现
	public function poolRequest($step,$datas,$status='completed')
	{
		$config = ['verify' => false,'timeout' => 8];		
		// 创建request对象
		$client = new Client();
        $requests = function ($total) use ($client,$datas,$config) {
            foreach ($datas as $data) {
            	$url = unicode_decode($data->url);
                yield function() use ($client,$url,$config) {
                    return $client->getAsync($url,$config);
                };
            }
        };
		$pool = new Pool($client, $requests(count($datas)), [
			// 每发5个请求
		    'concurrency' => count($datas),
		    'fulfilled' => function ($response, $index ) use($step,$datas,$status) {		        
		        // 文件保存路径
		        $file = PROJECT_APP_DOWN.$step.'/'.$datas[$index]->id.'.html';
		        // 校验回调成功
		        if($response->getStatusCode()==200)
		        {
		        	// 保存文件
		            file_put_contents($file,$response->getBody());
		            // 输出结果
		            echo $step.' '.$datas[$index]->id.'.html'." download successful!".PHP_EOL;
		        }
		        // 更改SQL语句
		        if(file_exists($file))  Capsule::table($step)->where('id', $datas[$index]->id)->update(['status' =>$status]);	    	
		    },
		    'rejected' => function ($reason, $index){
				echo 'request has been deny!'.PHP_EOL;
		    },
		]);

		// Initiate the transfers and create a promise
		$promise = $pool->promise();

		// Force the pool of requests to complete.
		$promise->wait();
	}




	// 获取JavaScript渲染后的HTML
	public function phantomjsDown($step,$data)
	{
		// 页面文件名
    	$file = PROJECT_APP_DOWN.$step.'/'.$data->id.'.html';
    	// 连接配置
		$config = array(
			'verify' => false,
			// 'proxy'=>'https://110.82.102.109:34098'
		);
		// 获取JavaScript渲染后的页面
		$client = PhantomJsClitent::getInstance();
		$client->getEngine()->setPath(APP_PATH.'/bin/phantomjs.exe');
		$request  = $client->getMessageFactory()->createRequest();
		$response = $client->getMessageFactory()->createResponse();
		$request->setMethod('GET');
		$request->setUrl($data->url);
		$client->send($request, $response);
		// 保存文件
		if($response->getStatus() === 200)
		{
			file_put_contents($file, $response->getContent());
			// 命令行执行时候不需要经过apache直接输出在窗口
		    echo $step.' '.$data->id.'.html'." download successful!".PHP_EOL;
		}
		// 更新状态
		if(file_exists($file))
    	{
            Capsule::table($step)
	            ->where('id', $data->id)
	            ->update(['status' =>'completed']);
    	}
	}

}

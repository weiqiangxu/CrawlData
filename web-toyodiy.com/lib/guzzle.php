<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

use GuzzleHttp\Pool;

class guzzle{

	// 按顺序处理单个异步请求
	public function down($step,$data)
	{
		// 页面文件名
    	$file = PROJECT_APP_DOWN.$step.'/'.$data->id.'.html';
		$client = new Client();

		$config = array(
			'verify' => false,
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
	    		// 标记已下载
	    		if(file_exists($file)) Capsule::table($step)->where('id', $data->id)->update(['status' =>'completed']);
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
		$jar = new \GuzzleHttp\Cookie\CookieJar();
		// 随机获取ip池的IP
		$json = json_decode(file_get_contents(__DIR__.'\ip.json'), true);


		foreach ($datas as $k => $v) {
			if(!empty($json['msg']))
			{
				$key = array_rand($json['msg'],1);
				$ip = $json['msg'][$key];
				// 使用随机获取的代理IP
				$datas[$k]->config = [
					'verify' => false,
					// 代理IP
					'proxy'=> "http://".$ip['ip'].':'.$ip['port'],
					// 开启cookie通话
					'cookies' => $jar,
				    'headers' => [
				    	// 浏览器获取的cookie
						'cookies' => 'S=1me93fcqmp0d3migtpauitvu11',
				    	'Accept-Encoding' => 'gzip, deflate',
				    	'Accept-Language' => 'zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2',
				    	'Connection' => 'keep-alive',
				    	'Host' => 'www.toyodiy.com',
				        'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.119 Safari/537.36',
				        'Accept'     => 'text/html,application/xhtml+xm…plication/xml;q=0.9,*/*;q=0.8'
				    ]
				];
			}
			else
			{
				// 无代理IP则使用真实IP
				$datas[$k]->config = array(
					'verify' => false,
				);
				$key = 'none';
			}
		}

		// 当前发送的请求的最小ID
		$minId = $datas[0]->id;

		// 创建request对象
		$client = new Client();

        $requests = function ($total) use ($client,$datas) {
            foreach ($datas as $data) {
            	// 字符转码
            	$url = unicode_decode($data->url);
            	$config = $data->config;
            	echo $config['proxy'].PHP_EOL;
                yield function() use ($client,$url,$config) {
                    return $client->getAsync($url,$config);
                };
            }
        };

		$pool = new Pool($client, $requests(count($datas)), [
			// 每发5个请求
		    'concurrency' => 5,
		    'fulfilled' => function ($response, $index ) use($step,$minId,$status) {		        
		        // 当前处理的发送请求的ID
		        $id = $index+(int)$minId;
		        // 文件保存路径
		        $file = PROJECT_APP_DOWN.$step.'/'.$id.'.html';
		        // 校验回调成功
		        if($response->getStatusCode()==200)
		        {
		        	// 保存文件
		            file_put_contents($file,$response->getBody());
		            // 命令行执行时候不需要经过apache直接输出在窗口
		            echo $step.' '.$id.'.html'." download successful!".PHP_EOL;
		        }
		        // 标记已下载
		        if(file_exists($file)) Capsule::table($step)->where('id', $id)->update(['status' =>$status]);		    	
		    },
		    'rejected' => function ($reason, $index) use($step,$minId,$key) {
		        // this is delivered each failed request
		        $id = $index+(int)$minId;
			    if(strpos($reason->getMessage(), 'Failed to connect to'))
			    {
		       		// 如果是IP无法连接就去除当前代理IP
			    	if($key == 'none' && $key!=0)
			    	{				    	
			    		echo 'ip has been deny!'.PHP_EOL;
			    	}
			    	else
			    	{
			    		$data = json_decode(file_get_contents(__DIR__.'\ip.json'), true);
				    	unset($data['msg'][$key]);
				    	file_put_contents(__DIR__.'\ip.json', json_encode($data));
				    	echo "del one ip port".PHP_EOL;
			    	}
			    }
			    else
			    {
			    	echo $step.' '.$id.'.html'." netError!".PHP_EOL;
			    }

		    },
		]);

		// Initiate the transfers and create a promise
		$promise = $pool->promise();

		// Force the pool of requests to complete.
		$promise->wait();
	}
}

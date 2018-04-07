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


	// 获取蘑菇代理IIP五个
	public function get_mogu_ip()
	{
		$file = json_decode(file_get_contents(__DIR__.'\ip.json'), true);
		if(empty($file))
		{
			$api = 'http://piping.mogumiao.com/proxy/api/get_ip_bs?appKey=9ef4e42d8dff456ba1f8e501ed02b0dd&count=10&expiryDate=0&format=1';
			// 蘑菇代理IP池
			$file = json_decode(file_get_contents($api),true);

			if(!is_array($file['msg']))
			{
				echo 'request mogu.com too fast!'.PHP_EOL;
				// 取得太频繁代理未返回
				sleep(3);
				// 重新获取
				$file = json_decode(file_get_contents($api),true);
			}
			// 截取5个
			$data = array_slice($file['msg'],0,10);
			
			// 剩余存档
			file_put_contents(__DIR__.'\ip.json', json_encode(array_slice($file['msg'],10)));
		}else{
			// 截取5个
			$data = array_slice($file,0,10);
			// 剩余存档
			file_put_contents(__DIR__.'\ip.json', json_encode(array_slice($file,10)));
		}
		// 一次性返回20个，其中22重复
		$data = array_merge($data,$data,$data,$data,$data,$data,$data,$data,$data,$data);

		return $data;
	}



	// 并发处理多个-5个
	public function poolRequest($step,$datas,$status='completed')
	{
		$jar = new \GuzzleHttp\Cookie\CookieJar();
		// 蘑菇代理IP池子
		$json = $this->get_mogu_ip();
		foreach ($datas as $k => $v) {
			// 代理ip
			$ip = $json[$k];
			// request
			$datas[$k]->config = [
				'timeout' => 8,
				'verify' => false,
				'proxy'=> "http://".$ip['ip'].':'.$ip['port'],
				'cookies' => $jar,
			    'headers' => [
					// 'cookies' => 'S=1me93fcqmp0d3migtpauitvu11',
					'cookies' => 'S='.uniqid(),
			    	'Accept-Encoding' => 'gzip, deflate',
			    	'Accept-Language' => 'zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2',
			    	'Connection' => 'keep-alive',
			    	'Host' => 'www.toyodiy.com',
			        'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.119 Safari/537.36',
			        'Accept'     => 'text/html,application/xhtml+xm…plication/xml;q=0.9,*/*;q=0.8'
			    ]
			];
		}


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
		    'concurrency' => count($datas),
		    'fulfilled' => function ($response, $index ) use($step,$status,$datas) {		        
		        // 文件保存路径
		        $file = PROJECT_APP_DOWN.$step.'/'.$datas[$index]->id.'.html';
		        // 校验回调成功
		        if($response->getStatusCode()==200)
		        {
		        	// 保存文件
		            file_put_contents($file,$response->getBody());
		        }
		        // 标记已下载
		        if(file_exists($file) && (filesize($file)>600) && !strpos(file_get_contents($file),'Accessing too fast'))
		        {
		        	Capsule::table($step)->where('id', $datas[$index]->id)->update(['status' =>$status]);
		        	 // 输出结果
		            echo $step.' '.$datas[$index]->id.'.html'." download successful!".PHP_EOL;
		        }
		        else
		        {
		        	unlink($file);
		        }		    	
		    },
		    'rejected' => function ($reason, $index) use($step,$datas) {
		        // this is delivered each failed request
			    echo $step.' '.$datas[$index]->id.'.html'." netError!".PHP_EOL;
		    },
		]);

		// Initiate the transfers and create a promise
		$promise = $pool->promise();

		// Force the pool of requests to complete.
		$promise->wait();
	}
}

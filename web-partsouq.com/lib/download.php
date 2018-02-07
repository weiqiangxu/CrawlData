<?php

class mineload{
	/** 
		* curl 获取 https 请求 
		* @param String $url        请求的url 
	*/  
	public function curl_https($url)
	{
		// 采集到的url会被pdo入库时候转义为html字符现在转义回来防止网站无法读取到任何的get参数
		$url = html_entity_decode($url);
		// 从文件中读取数据到PHP变量
		$json_string = file_get_contents(__DIR__.'\ip.json');

		// 把JSON字符串转成PHP数组
		$data = json_decode($json_string, true);


		if(empty($data['msg']))
		{
			$res = self::send_request($url);
			if($res['error']!="")
			{
				echo " request has be ignore!\r\n";
				die;
			}
		}
		else
		{
			$ip_config = current($data['msg']);
			$res = self::send_request($url,$ip_config['ip'],$ip_config['port']);
			// 如果用代理ip有问题把这个ip文件内容去除
			if($res['error'] != "")
			{
				array_shift($data['msg']);
				file_put_contents(__DIR__.'\ip.json', json_encode($data));
				echo "change ip port\r\n";
				$res = self::send_request($url);
			}
		}
		// 返回数值
		return $res;
	}

	public static function send_request($url,$ip="",$port="")
	{
		$ch = curl_init();  
	    // 伪造来源IP
	    // $header = array('X-FORWARDED-FOR:111.222.333.4', 'CLIENT-IP:111.222.333.4');
	    // 传递头信息
	    // curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	    // 访问的来源（骗他是百度搜索结果过来的)图片防盗链需要
	    curl_setopt($ch, CURLOPT_REFERER, "https://partsouq.com/en");  
	    // 用户代理,标志浏览器发出而非cURL.是一种向访问网站提供你所使用的浏览器类型及版本、操作系统及版本、浏览器内核、等信息的标识。
	    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.57 Safari/536.11");
	    // 有代理IP就指定代理IP
	    if($ip!="" && $port!="")
	    {
	    	curl_setopt($ch, CURLOPT_PROXY, "http://".$ip.":".$port);  
	    }
	    // 跳过证书检查
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
	    // 从证书中检查SSL加密算法是否存在
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	    // 实例化url句柄
	    curl_setopt($ch, CURLOPT_URL, $url); 
	    // 是否将输出流变量形式存储
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// 成功连接服务器前最长等待时长（单位秒数）
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 30);
		// 连接服务器后，接收缓冲完成前需要等待最大时长限定，目标文件巨大是否会非常需要（单位秒数）
		curl_setopt($ch, CURLOPT_TIMEOUT, 5000);
	  	// 执行结果
	    $html = curl_exec($ch);  
	    // 返回头信息
        $info = curl_getinfo($ch);
        // 返回报错信息
        $error = curl_error($ch);
        // 关闭资源句柄
	    curl_close($ch);

	    return array('info'=>$info,'html'=>$html,'error'=>$error);
	}
}

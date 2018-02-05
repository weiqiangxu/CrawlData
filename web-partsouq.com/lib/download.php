<?php

class mineload{
	/** 
		* curl 获取 https 请求 
		* @param String $url        请求的url 
		* @param Array  $header     请求时发送的header 
		* @param int    $timeout    超时时间，默认30s 
	*/  
	public function curl_https($url, $header=array(), $timeout=30)
	{
	    $ch = curl_init();  
	    // 跳过证书检查
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
	    // 从证书中检查SSL加密算法是否存在
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	    // 实例化url句柄
	    curl_setopt($ch, CURLOPT_URL, $url); 
	    // 传递头信息
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	    // 是否将输出流变量形式存储
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    // 连接服务器后，接收缓冲完成前需要等待最大时长限定，目标文件巨大是否会非常需要（单位秒数）
	    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);  
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

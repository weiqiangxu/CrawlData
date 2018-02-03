<?php

class mineload{
	/** 
		* curl 获取 https 请求 
		* @param String $url        请求的url 
		* @param Array  $data       要发送的数据 
		* @param Array  $header     请求时发送的header 
		* @param int    $timeout    超时时间，默认30s 
	*/  
	public function curl_https($url, $data=array(), $header=array(), $timeout=30)
	{
	    $ch = curl_init();  
	    // 跳过证书检查
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
	    // 从证书中检查SSL加密算法是否存在
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  
	    curl_setopt($ch, CURLOPT_URL, $url);  
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);  
	    curl_setopt($ch, CURLOPT_POST, true);  
	    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));  
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
	    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);  
	  	// 执行结果
	    $html = curl_exec($ch);  
	    // 返回头信息
        $info = curl_getinfo($ch);
        // 返回报错信息
        $error = curl_error($ch);
        // 关闭
	    curl_close($ch);

	    return array('info'=>$info,'html'=>$html,'error'=>$error);
	}
}

<?php

class mineload{
	// 一个自定义全局方法
	public function curldownpage($url)
	{
		// 防止foreach跑的太快未获取到内容直接报prev_sibling none object
		$ch = curl_init();
		// 2. 设置选项，包括URL
		curl_setopt($ch,CURLOPT_URL,$url);
		// 设置获取到内容不直接输出到页面上
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		// 设置等待时间无限长，强制必须获取到内容
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT_MS,0);
		// CURLLOPT_HEADER设置为0表示不返回HTTP头部信息
		curl_setopt($ch,CURLOPT_HEADER,0);
		
		curl_setopt ($ch, CURLOPT_TIMEOUT, 300);
		// 3. 执行并获取HTML文档内容
		$temp = curl_exec($ch);
		// 响应码
		$info = curl_getinfo($ch);
		// 4. 释放curl句柄
		curl_close($ch);

		$res = array('html'=>$temp,'info'=>$info);

		return $res;
	}
	
}
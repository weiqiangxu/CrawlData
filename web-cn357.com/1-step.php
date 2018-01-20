<?php

// 加载composer资源库
require_once('../resources/autoload.php');
// 加载自己项目资源库
require_once('./lib/config.php');
// 加载自己项目资源库
require_once('./lib/initable.php');

// 改变发送给客户端的http头信息，告诉浏览器应该以中文编码形式解释返回的内容,覆盖vender库的编码声明
header("Content-type: text/html; charset=gb2312"); 


// 需要读取的批次
$pici = [
	301,300
];

// 需要读取的批次
initable::$pici = $pici;

// 初始化要下载的列表页
initable::initlist();
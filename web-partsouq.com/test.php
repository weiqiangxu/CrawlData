<?php

// 测试防屏蔽是否有效
require_once('./lib/download.php');
$mineload = new mineload();
$res = $mineload->curl_https('https://partsouq.com');
var_dump($res['error']);
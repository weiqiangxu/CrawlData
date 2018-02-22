<?php

// 加载composer资源库
require_once('../resources/autoload.php');
// 初始化数据库配置
require_once('./lib/config.php');
// 加载自己项目资源库
require_once('./lib/twostep.php');
// 封装下载类
require_once('./lib/guzzle.php');
// 开始执行
twostep::initdetail();
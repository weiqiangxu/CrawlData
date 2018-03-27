<?php

// 加载composer资源库
require_once('../resources/autoload.php');
// 初始化数据库配置
require_once('./lib/config.php');
// 加载自己项目资源库
require_once('./lib/twostep.php');
// 封装下载类
require_once('./lib/guzzle.php');

// 对筛选条件拼接，直到每一个页面的shown数量小于100
twostep::car_part();
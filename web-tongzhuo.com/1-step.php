<?php

// 加载composer资源库
require_once('../resources/autoload.php');
// 初始化数据库配置
require_once('./lib/config.php');
// 加载自己项目资源库
require_once('./lib/onestep.php');
// 封装下载类
require_once('./lib/guzzle.php');

// 获取所有的品牌
onestep::initable();
// 解析获取所有数据
onestep::bris_ac_uk();


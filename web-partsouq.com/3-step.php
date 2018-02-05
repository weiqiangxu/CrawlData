<?php

// 加载composer资源库
require_once('../resources/autoload.php');
// 初始化数据库配置
require_once('./lib/config.php');
// 加载自己项目资源库
require_once('./lib/threestep.php');
// 自己的下载类
require_once('./lib/download.php');

// 获取所有的pic链接页面
// threestep::download();

// 获取所有的下载好的pic页面
threestep::analyse();
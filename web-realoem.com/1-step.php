<?php

// 加载composer资源库
require_once('../resources/autoload.php');
// 初始化数据库配置
require_once('./lib/config.php');
// 加载自己项目资源库
require_once('./lib/onestep.php');
// 自己的下载类
require_once('./lib/download.php');
// 路径处理类
require_once('./lib/LibDir.php');
// 文件处理类
require_once('./lib/LibFile.php');

// 获取所有的body链接
onestep::body();

// 获取所有的model链接
onestep::model();

// 获取所有的market链接
onestep::market();

// 获取所有的prod链接
onestep::prod();

// 解析获取原生数据
onestep::rawdata();
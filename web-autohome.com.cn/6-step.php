<?php

// 加载composer资源库
require_once('../resources/autoload.php');
// 初始化数据库配置
require_once('./lib/config.php');
// 加载自己项目资源库
require_once('./lib/sixstep.php');
// 封装下载类
require_once('./lib/guzzle.php');

// 分析获取建表语句
// sixstep::get();

// 建表
// sixstep::initable();

// 转储数据
// sixstep::move();

// 检测是否有js类名未转换
// sixstep::echoRejs();

// 最终数据检查
// sixstep::check();


// 整理数据
sixstep::manage();
<?php
// 设置脚本超时
set_time_limit(0);
// 内存限制
ini_set('memory_limit', '2014M');
// 默认时区
date_default_timezone_set('PRC');
// 声明编码
@header('Content-type:text/html;charset=utf-8');

// 类文件资源目录
defined('APP_PATH') or define('APP_PATH', __DIR__);
// 下载文件存储路径
defined('APP_DOWN') or define('APP_DOWN', 'D:/Catch');

defined('MAX_FILE_SIZE') or define('MAX_FILE_SIZE', 60000000);

// 加载第三方模块
require APP_PATH . '/vendor/autoload.php';

// 设置默认数据库
$database = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'information_schema',
    'username' => 'root',
    'password' => '123456',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => ''
];
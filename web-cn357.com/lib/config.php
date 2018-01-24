<?php
// 数据库操作对象
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
// 创建链接
$capsule->addConnection($database,'getdatabases');
// 设置全局静态可访问
$capsule->setAsGlobal();
// 启动Eloquent
$capsule->bootEloquent();

$Schema = [];
// 检测数据库是否存在
$Query = Capsule::connection('getdatabases')->select('show databases');

foreach ($Query as $Table)
{
    $Schema[] = $Table->Database;
}

// 用于存储原始数据数据的数据库
$database = array_merge($database, ['database' => 'temp_cn357_'.date("Ymd",time())]);

if (!in_array('temp_cn357_'.date("Ymd",time()),$Schema))
{
	// 如果数据库不存在则创建数据库
    $SQL = sprintf('create database `%s` character set utf8 collate utf8_unicode_ci', 'temp_cn357_'.date("Ymd",time()));
    Capsule::connection('getdatabases')->statement($SQL);
}

// 创建默认数据库连接
$capsule->addConnection($database);
// 设置全局静态可访问
$capsule->setAsGlobal();
// 启动Eloquent
$capsule->bootEloquent();


// 检测是否整理后的数据库存储库
$final_database = array_merge($database, ['database' => 'final_database']);

if (!in_array('final_database',$Schema))
{
	// 如果数据库不存在则创建数据库
    $SQL = sprintf('create database `final_database` character set utf8 collate utf8_unicode_ci');
    Capsule::connection('getdatabases')->statement($SQL);
}

// 创建最终数据库连接
$capsule->addConnection($final_database,'final_database');
// 设置全局静态可访问
$capsule->setAsGlobal();
// 启动Eloquent
$capsule->bootEloquent();


// 定义下载文件存储路径
define('PROJECT_APP_DOWN',APP_DOWN.'/wwwcn357com/');

// 客户端关闭脚本终止
ignore_user_abort(true);

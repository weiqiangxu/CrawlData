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

// 用于存储的数据库
$database = array_merge($database, ['database' => 'wwwcn357com_database']);

if (!in_array('wwwcn357com_database',$Schema))
{
	// 如果数据库不存在则创建数据库
    $SQL = sprintf('create database `%s` character set utf8 collate utf8_unicode_ci', 'wwwcn357com_database');
    Capsule::connection('getdatabases')->statement($SQL);
}

// 创建默认数据库连接
$capsule->addConnection($database);
// 设置全局静态可访问
$capsule->setAsGlobal();
// 启动Eloquent
$capsule->bootEloquent();

// 定义当前项目路径
define('PROJECTPATH',str_replace ( '\\', '/',dirname(dirname(__FILE__)).'/'));

// 客户端关闭脚本终止
ignore_user_abort(true);

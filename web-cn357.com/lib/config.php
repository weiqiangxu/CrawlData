<?php
// 数据库操作对象
use Illuminate\Database\Capsule\Manager as Capsule;

// 数据库连接信息
$database = [
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'database',
    'username'  => 'root',
    'password'  => '123456',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
];
$capsule = new Capsule;
// 创建链接
$capsule->addConnection($database);
// 设置全局静态可访问
$capsule->setAsGlobal();
// 启动Eloquent
$capsule->bootEloquent();
// 数据库迁移

define('PROJECTPATH',str_replace ( '\\', '/',dirname(dirname(__FILE__)).'/'));

// 客户端关闭脚本终止
ignore_user_abort(true);

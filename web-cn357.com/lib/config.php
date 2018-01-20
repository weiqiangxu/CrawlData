<?php
// 数据库操作对象
use Illuminate\Database\Capsule\Manager as Capsule;
// 数据库迁移对象
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
// 数据库连接信息
$database = [
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'database',
    'username'  => 'root',
    'password'  => '123456',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
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

// Schema::create('flights', function (Blueprint $table) {
// 	$table->increments('id');
// 	$table->string('name');
// 	$table->string('airline');
// 	$table->timestamps();
// });

die;
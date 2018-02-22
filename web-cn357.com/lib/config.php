<?php
// 数据库操作对象
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
// 创建链接
$capsule->addConnection($database,'schema');
// 设置全局静态可访问
$capsule->setAsGlobal();
// 启动Eloquent
$capsule->bootEloquent();

$Schema = [];
// 检测数据库是否存在
$Query = Capsule::connection('schema')->select('show databases');

foreach ($Query as $Table)
{
    $Schema[] = $Table->Database;
}

// 用于存储原始数据数据的数据库
$database = array_merge($database, ['database' => 'temp_cn357_'.date("Ym",time())]);

if (!in_array('temp_cn357_'.date("Ym",time()),$Schema))
{
	// 如果数据库不存在则创建数据库
    $SQL = sprintf('create database `%s` character set utf8 collate utf8_unicode_ci', 'temp_cn357_'.date("Ym",time()));
    Capsule::connection('schema')->statement($SQL);
}

// 创建默认数据库连接
$capsule->addConnection($database);
// 设置全局静态可访问
$capsule->setAsGlobal();
// 启动Eloquent
$capsule->bootEloquent();


// 检测是否整理后的数据库存储库
$final_database = array_merge($database, ['database' => 'model_jdcswww']);

if (!in_array('model_jdcswww',$Schema))
{
	// 如果数据库不存在则创建数据库
    $SQL = sprintf('create database `model_jdcswww` character set utf8 collate utf8_unicode_ci');
    Capsule::connection('schema')->statement($SQL);
}

// 创建最终数据库连接
$capsule->addConnection($final_database,'model_jdcswww');
// 设置全局静态可访问
$capsule->setAsGlobal();
// 启动Eloquent
$capsule->bootEloquent();


// 定义下载文件存储路径
define('PROJECT_APP_DOWN',APP_DOWN.'/wwwcn357com/');

// 客户端关闭脚本终止
ignore_user_abort(true);

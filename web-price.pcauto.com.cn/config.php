<?php
include '../resources/autoload.php';
use Illuminate\Database\Capsule\Manager as Capsule;

$database = array_merge($database, ['database' => 'temp_pcauto_20180119']);
$capsule = new Capsule;
// 创建链接
$capsule->addConnection($database);
// 设置全局静态可访问
$capsule->setAsGlobal();
// 启动Eloquent
$capsule->bootEloquent();
<?php

// 加载composer资源库
require_once('../resources/autoload.php');
// 初始化数据库配置
require_once('./lib/config.php');
// 加载自己项目资源库
require_once('./lib/onestep.php');

// 初始化所有表格
onestep::initable();

// 获取所有的brand汽车品牌链接
onestep::brand();

// 获取所有的model汽车系列链接
onestep::model();
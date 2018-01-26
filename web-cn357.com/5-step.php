<?php

// 加载composer资源库
require_once('../resources/autoload.php');
// 初始化数据库配置
require_once('./lib/config.php');
// 加载自己项目资源库
require_once('./lib/fivestep.php');
// 路径处理类
require_once('./lib/LibDir.php');
// 文件处理类
require_once('./lib/LibFile.php');

// 新建整理后的存储表
fivestep::initable();

// 初始化批次以及对应发布时间
fivestep::initDataPost();

// 整理数据
fivestep::cleandata();
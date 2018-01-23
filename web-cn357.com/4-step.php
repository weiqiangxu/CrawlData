<?php

// 加载composer资源库
require_once('../resources/autoload.php');
// 初始化数据库配置
require_once('./lib/config.php');
// 加载自己项目资源库
require_once('./lib/fourstep.php');
// 路径处理类
require_once('./lib/LibDir.php');
// 文件处理类
require_once('./lib/LibFile.php');
// 中文转拼音
require_once('./lib/pinyin.php');

// 初始化数据表
fourstep::initable();

// 解析所有文件
fourstep::analyse();
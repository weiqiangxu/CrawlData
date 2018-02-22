<?php

// 加载composer资源库
require_once('../resources/autoload.php');
// 初始化数据库配置
require_once('./lib/config.php');
// 加载自己项目资源库
require_once('./lib/onestep.php');
// 封装下载类
require_once('./lib/guzzle.php');

// 初始化表
onestep::initable();

// 录入需要更新的
onestep::judgeupdate();

// 根据最大页码初始化列表页
onestep::initlist();

// 下载列表页
onestep::loadList();
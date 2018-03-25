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

// Warwick
onestep::Warwick();

// bris_ac_uk
onestep::bris_ac_uk();

// 剑桥
onestep::jianqiao();
// 牛津
onestep::niujin();

// 帝国理工
onestep::diguo();

// lse
onestep::lse();

// ucl
onestep::ucl();

// Edinburgh
onestep::Edinburgh();

// KCL
onestep::KCL();

// Manchester
onestep::Manchester();

// Bristol
onestep::Bristol();

// Glasgow
onestep::Glasgow();

// Durham
onestep::Durham();

// Sheffield
onestep::Sheffield();

// Queenmary
onestep::Queenmary();

// Exeter
onestep::Exeter();

// Southampton
onestep::Southampton();

// York
onestep::York();

// Leeds
onestep::Leeds();

// Birmingham
onestep::Birmingham(); 

// St_Andrews
onestep::St_Andrews();

// Nottingham
onestep::Nottingham();

// Sussex
onestep::Sussex();

// Lancaster
onestep::Lancaster();

// Leicester
onestep::Leicester();

// Cardiff
onestep::Cardiff();

// Newcastle
onestep::Newcastle();

// Liverpool
onestep::Liverpool();

// Aberdeen
onestep::Aberdeen();

// Dundee
onestep::Dundee();

// RHUL
onestep::RHUL();

// Queen_belfast
onestep::Queen_belfast();

// Reading
onestep::Reading();

// Bath
onestep::Bath();

// Essex
onestep::Essex();

// Swansea
onestep::Swansea();

// Loughborough_University
onestep::Loughborough_University();

// Goldsmiths
onestep::Goldsmiths();

// Stirling
onestep::Stirling();

// Kent
// onestep::Kent();

//有问题的方法
// onestep::Bangor_University();
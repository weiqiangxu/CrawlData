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

$newDbName = 'temp_tongzhuo_'.date("Ym",time());

// 用于存储原始数据数据的数据库
$database = array_merge($database, ['database' => $newDbName]);

if (!in_array($newDbName,$Schema))
{
	// 如果数据库不存在则创建数据库
    $SQL = sprintf('create database `%s` character set utf8 collate utf8_unicode_ci', $newDbName);
    Capsule::connection('schema')->statement($SQL);
}

// 创建默认数据库连接
$capsule->addConnection($database);
// 设置全局静态可访问
$capsule->setAsGlobal();
// 启动Eloquent
$capsule->bootEloquent();

// 定义下载文件存储路径
define('PROJECT_APP_DOWN',APP_DOWN.'/www_tongzhuo_com/');

// 客户端关闭脚本终止
ignore_user_abort(true);

/**
* $str Unicode编码后的字符串
* $decoding 原始字符串的编码，默认utf-8
* $prefix 编码字符串的前缀，默认"&#"
* $postfix 编码字符串的后缀，默认";"
*/
function unicode_decode($unistr, $encoding = 'utf-8', $prefix = '&#', $postfix = ';')
{	
	$orig_str= $unistr;
	$arruni = explode($prefix, $unistr);
	$unistr = '';
	for ($i = 1, $len = count($arruni); $i < $len; $i++)
	{
		if (strlen($postfix) > 0) {
			$arruni[$i] = substr($arruni[$i], 0, strlen($arruni[$i]) - strlen($postfix));
		}
		$temp = intval($arruni[$i]);
		$unistr .= ($temp < 256) ? chr(0) . chr($temp) : chr($temp / 256) . chr($temp % 256);
	}
	$str = str_split(iconv('UCS-2', $encoding, $unistr));

	foreach ($str as $v)
	{
		$orig_str = preg_replace('/&#[\S]+?;/', $v, $orig_str,1);
	}
	return $orig_str;
}
<?php

// 数据库操作对象
use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
// temp_toyodiy.com库
$capsule->addConnection(array_merge($database, ['database' => 'temp_toyodiy.com']),'vin_list');
$capsule->setAsGlobal();
$capsule->bootEloquent();

// schema库
$capsule->addConnection($database,'schema');
$capsule->setAsGlobal();
$capsule->bootEloquent();

// temp_toyodiy_20180329库
$Schema = [];
$Query = Capsule::connection('schema')->select('show databases');
foreach ($Query as $Table){
    $Schema[] = $Table->Database;
}
$name = 'temp_toyodiy_'.date("Ym",time());
$database = array_merge($database, ['database' => $name]);
if (!in_array($name,$Schema)){
	// 不存在则新建
    $SQL = sprintf('create database `%s` character set utf8 collate utf8_unicode_ci', $name);
    Capsule::connection('schema')->statement($SQL);
}
$capsule->addConnection($database);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// yp_realoem库
$capsule->addConnection(array_merge($database, ['database' => 'yp_realoem']),'yp_realoem');
$capsule->setAsGlobal();
$capsule->bootEloquent();

// 定义下载文件存储路径
define('PROJECT_APP_DOWN',APP_DOWN.'/www_toyodiy_com/');

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
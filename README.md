# CollectionData

### 如何使用：

1.  配置resource/autoload.php的数据库information_schema信息。

	$database = [
	    'driver' => 'mysql',
	    'host' => 'localhost',
	    'database' => 'information_schema',
	    'username' => 'root',
	    'password' => '123456',
	    'charset' => 'utf8',
	    'collation' => 'utf8_unicode_ci',
	    'prefix' => ''
	];

2.  依次执行1-step.php、2-step.php、3-step.php、4-step.php。

***

*1-step.php会根据数据库表 url_list 和 http://www.cn357.com/notice_list 的最大批次号检测需要更新的批次号。*

*3-step.php在关闭客户端之后,再次执行会继续原来的下载。其他脚本必须一次性执行完成所有。*

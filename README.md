# 数据爬取工具包

### 如何使用web-cn357.com：

1.  配置resource/autoload.php的数据库信息。

2.  依次执行1-step.php、2-step.php、3-step.php、4-step.php、5-step.php。

***

### 注意：

1.  *1-step.php会根据数据库表 url_list 和 [商车网汽车公告批次](http://www.cn357.com/notice_list/) 的最大批次号检测需要更新的批次号。*

2.  *3-step.php在关闭客户端之后,再次执行会继续原来的下载。其他脚本必须一次性执行完成所有。*

3.  *执行结果：自动创建的数据库 temp_cn357_201801 存储原始数据。最终清洗后的数据存储 model_jdcsww*

***

### 如何使用web-realoem.com：

1.  配置resource/autoload.php的数据库信息。

2.  依次执行1-step.php、2-step.php、3-step.php、4-step.php、5-step.php。

***

### 注意：

1.  *1-step.php会读取[RealOEM.com](http://www.realoem.com/bmw/enUS/select?product=P&archive=0) Series且必须一次性执行完不能出错否则需要删除数据库重新来过*

2.  *2-step.php、3-step.php、4-step.php、5-step.php加入过滤网站防DDOS攻击页面操作、数据字段唯一、断点下载，所以可以随时关闭随时开启无限开启命令行窗口运行，但是要按顺序执行并且执行完毕否则出现数据不全，过程之中dom解析入库时候虽然加入isEmpty校验但是由于数据库并发请求会出现pdo exception 字段唯一导致插入失败，这是正常的，关掉这个宕掉的进程就可以，数据不会错就行了。*

4.  *每个脚本都需要执行多几次直到没有任何输出（5-step.php会输出 analyse is completed !），下载的当curl请求一直未返回也会结束脚本，analyse也会出现漏掉几个页面没解析的，但是有字段记录所以多次执行可以完全读完但不会出错*

4.  *执行结果：自动创建的数据库 temp_realoem_201801 - rawdata 存储原始数据。*
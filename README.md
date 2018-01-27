# CollectionData

### 如何使用：

1.  配置resource/autoload.php的数据库信息。

2.  依次执行1-step.php、2-step.php、3-step.php、4-step.php、5-step.php。

***

### 注意：

1.  *1-step.php会根据数据库表 url_list 和 [商车网汽车公告批次](http://www.cn357.com/notice_list/) 的最大批次号检测需要更新的批次号。*

2.  *3-step.php在关闭客户端之后,再次执行会继续原来的下载。其他脚本必须一次性执行完成所有。*

3.  *执行结果：自动创建的数据库 temp_cn357_201801 存储原始数据。最终清洗后的数据存储 model_jdcsww *
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

1.  *数据来源：[RealOEM.com](http://www.realoem.com/bmw/enUS/select?product=P&archive=0)*

2.  *每个脚本可以多开窗口采集并支持断点采集，并且请确保每个脚本执行完毕否则出现数据不全*

4.  *多窗口执行指令过程之中可能会出现pdo exception url unique 这是多窗口并发插入导致的，因为加入unque防止数据重复(尽管insert之前isEmpty校验过)，只要最终数据是正确的就可以*

4.  *执行结果：自动创建的数据库 temp_realoem_201801 - rawdata 存储原始数据。*

***

### 如何使用web-partsouq.com：

1.  配置resource/autoload.php的数据库信息。

2.  依次执行1-step.php、2-step.php、3-step.php。

***

### 注意：

1.  *数据来源：[partsouq.com](https://partsouq.com/)*

2.  *每个脚本可以多开窗口采集并支持断点采集，并且请确保每个脚本执行完毕否则出现数据不全*

4.  *执行结果：自动创建的数据库 temp_partsouq_201801 - rawdata 存储原始数据。*
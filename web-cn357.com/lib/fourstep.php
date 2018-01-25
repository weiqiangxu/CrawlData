<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;


use Overtrue\Pinyin\Pinyin;

/**
  * 整理原生数据
  * @author xu
  * @copyright 2018/01/24
  */
class fourstep{

	// 存储原生数据
	public function analyse()
	{
		// chunk分块处理每100条数据
		Capsule::table('url_detail')->where('status', 'completed')->orderBy('id')->chunk(100,function($datas){
			$pinyin = new Pinyin();
			// 日志操作类
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'fourstep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 保存文件名
		    	$file = PROJECT_APP_DOWN.'url_detail/'.$data->file_path.'/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (is_file($file))
		    	{
		    		$temp = file_get_contents($file);
					// 创建dom对象
					if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 获取所有的详情页下载链接
						$temp = array();
						// 获取相应节点数据
						$temp = array();
						// 获取数据表格之中所有tr的dom节点
						foreach($dom->find('.noticeAttr tr') as $article) {
							if(!$article->find('table',0))
							{
								// 将中文转拼音作为入库字段
								if($article->find('.t',0))
								{
									$k = $pinyin->abbr(mb_convert_encoding(trim($article->find('.t',0)->plaintext),"UTF-8", "gb2312"));
									$v = mb_convert_encoding($article->find('.t',0)->next_sibling()->plaintext,"UTF-8", "gb2312");
									$temp[$k] = $v;
								}
								// 有时候tr只有一个.t的td,排除non-object的exception
								if($article->find('.t',1))
								{
									$k = $pinyin->abbr(mb_convert_encoding(trim($article->find('.t',1)->plaintext),"UTF-8", "gb2312"));
									$v = mb_convert_encoding($article->find('.t',1)->next_sibling()->plaintext,"UTF-8", "gb2312");
									$temp[$k] = $v;
								}
							}
							else
							{
								// 处理发动机一行
								// 发动机型号
								$k = $pinyin->abbr(mb_convert_encoding(trim($article->find('.f1',0)->plaintext),"UTF-8", "gb2312")); 
								$v = mb_convert_encoding($article->find('.f',0)->next_sibling()->children(0)->innertext,"UTF-8", "gb2312");
								$temp[$k] = $v;
								// 发动机生产企业
								$k = $pinyin->abbr(mb_convert_encoding(trim($article->find('.f2',0)->plaintext),"UTF-8", "gb2312"));
								$v = mb_convert_encoding($article->find('.f',0)->next_sibling()->children(1)->innertext,"UTF-8", "gb2312");
								$temp[$k] = $v;
								// 发动机商标
								$k = $pinyin->abbr(mb_convert_encoding(trim($article->find('.f3',0)->plaintext),"UTF-8", "gb2312"));
								$v = mb_convert_encoding($article->find('.f',0)->next_sibling()->children(2)->innertext,"UTF-8", "gb2312");
								$temp[$k] = $v;
								// 排量
								$k = $pinyin->abbr(mb_convert_encoding(trim($article->find('.f3',0)->next_sibling()->plaintext),"UTF-8", "gb2312"));
								$v = mb_convert_encoding($article->find('.f',0)->next_sibling()->children(3)->innertext,"UTF-8", "gb2312");
								$temp[$k] = $v;
								// 功率
								$k = $pinyin->abbr(mb_convert_encoding(trim($article->find('.f',0)->last_child()->plaintext),"UTF-8", "gb2312"));
								$v = mb_convert_encoding($article->find('.f',0)->next_sibling()->children(4)->innertext,"UTF-8", "gb2312");
								$temp[$k] = $v;
							}
						}
						// 加入批次路径
						$temp['pclj'] = $data->file_path;
						// 加入当前页面url以供随机查询校验
						$temp['ymdz'] = $data->company_url;
						// 插入记录
						Capsule::table('raw_data')->insert($temp);
						// 清理内存防止内存泄漏
						$dom->clear();
						// 记录成功
					    $LibFile->WriteData($logFile, 4, $data->file_path.'/'.$data->id.'.html'.'解析完成！');
						echo $data->file_path.'/'.$data->id.'.html'." analyse ok!\r\n";
					}
					else
					{
						// 记录错误
					    $LibFile->WriteData($logFile, 4, $data->file_path.'/'.$data->id.'.html'.'文件不存在！');
						echo $data->file_path.'/'.$data->id.'.html'." file no exit!\r\n";
					}
			    }
		    }
		});
	}
}
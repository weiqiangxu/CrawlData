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
			$chineseToEn = array( '公告型号' => 'ggxh',
							'公告批次' => 'ggpc',
							'品牌' => 'pp',
							'类型' => 'lx', 
							'额定质量' => 'edzl',
							'总质量' => 'zzl',
							'整备质量' => 'zbzl',
							'燃料种类' => 'rlzl', 
							'排放依据标准' => 'pfyjbz' ,
							'轴数' => 'zs',
							'轴距' => 'zj',
							'轴荷' => 'zh',
							'弹簧片数' => 'thps', 
							'轮胎数' => 'lts', 
							'轮胎规格' => 'ltgg' ,
							'接近离去角' => 'jjlqj',
							'前悬后悬' => 'qxhx' ,
							'前轮距' => 'qlj', 
							'后轮距' => 'hlj' ,
							'识别代号' => 'sbdh', 
							'整车长' => 'zcz', 
							'整车宽' => 'zck',
							'整车高' => 'zcg',
							'货厢长' => 'hxz', 
							'货厢宽' => 'hxk',
							'货厢高' => 'hxg', 
							'最高车速' => 'zgcs', 
							'额定载客' => 'edzk', 
							'驾驶室准乘人数' => 'jsszcrs', 
							'转向形式' => 'zxxs', 
							'准拖挂车总质量' => 'ztgczzl',
							'载质量利用系数' => 'zzllyxs', 
							'半挂车鞍座最大承载质量' => 'bgcazzdczzl', 
							'企业名称' => 'qymc',
							'企业地址' => 'qydz', 
							'电话号码' => 'dhhm', 
							'传真号码' => 'czhm' ,
							'邮政编码' => 'yzbm',
							'底盘1' => 'dp1',
							'底盘2' => 'dp2',
							'底盘3' => 'dp3',
							'底盘4' => 'dp4',
							'备注' => 'bz',
							'发动机型号' => 'fdjxh',
							'发动机生产企业' => 'fdjscqy',
							'发动机商标' => 'fdjsb',
							'排量' => 'pl',
							'功率' => 'gl'
						);
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
		    		// 页面是gb2312的转换编码
		    		$temp = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");
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
									$k = trim($article->find('.t',0)->plaintext);
									$v = $article->find('.t',0)->next_sibling()->plaintext;
									$temp[$k] = $v;
								}
								// 有时候tr只有一个.t的td,排除non-object的exception
								if($article->find('.t',1))
								{
									$k = trim($article->find('.t',1)->plaintext);
									$v = $article->find('.t',1)->next_sibling()->plaintext;
									$temp[$k] = $v;
								}
							}
							else
							{
								// 处理发动机一行
								// 发动机型号
								$k = trim($article->find('.f1',0)->plaintext); 
								$v = $article->find('.f',0)->next_sibling()->children(0)->innertext;
								$temp[$k] = $v;
								// 发动机生产企业
								$k = trim($article->find('.f2',0)->plaintext);
								$v = $article->find('.f',0)->next_sibling()->children(1)->innertext;
								$temp[$k] = $v;
								// 发动机商标
								$k = trim($article->find('.f3',0)->plaintext);
								$v = $article->find('.f',0)->next_sibling()->children(2)->innertext;
								$temp[$k] = $v;
								// 排量
								$k = trim($article->find('.f3',0)->next_sibling()->plaintext);
								$v = $article->find('.f',0)->next_sibling()->children(3)->innertext;
								$temp[$k] = $v;
								// 功率
								$k = trim($article->find('.f',0)->last_child()->plaintext);
								$v = $article->find('.f',0)->next_sibling()->children(4)->innertext;
								$temp[$k] = $v;
							}
						}
						$newTemp = array();
						foreach ($temp as $key => $value) {
							$key = $chineseToEn[$key];
							$newTemp[$key] = $value; 
						}
						// 加入批次路径
						$newTemp['pclj'] = $data->file_path;
						// 加入当前页面url以供随机查询校验
						$newTemp['ymdz'] = $data->company_url;
						// 插入记录
						Capsule::table('raw_data')->insert($newTemp);
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
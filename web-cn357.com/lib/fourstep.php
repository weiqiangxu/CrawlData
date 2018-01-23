<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;

// 初始化待下载的页面地址表
class fourstep{

	// 初始化存储原始数据表
	public static function initable()
	{
		// 检测数据库是否存在如果不存在就删除
		Capsule::schema()->dropIfExists('raw_data');
		echo "raw_data delete\r\n";
		Capsule::schema()->create('raw_data', function (Blueprint $table) {
		    $table->increments('id');
		    // 批次路径
		    $table->string('picilujing')->comment('批次路径');			
			// 页面地址
			$table->string('yemiandizhi')->comment('页面地址');
			// 公告型号
			$table->string('gonggaoxinghao')->nullable()->comment('公告型号');
			// 公告批次
			$table->string('gonggaopici')->nullable()->comment('公告批次');
			// 品牌
			$table->string('pinpai')->nullable()->comment('品牌');
			// 类型
			$table->string('leixing')->nullable()->comment('类型');
			// 额定质量
			$table->string('edingzhiliang')->nullable()->comment('额定质量');
			// 总质量
			$table->string('zongzhiliang')->nullable()->comment('总质量');
			// 装备质量
			$table->string('zhengbeizhiliang')->nullable()->comment('装备质量');
			// 燃料种类
			$table->string('ranliaozhonglei')->nullable()->comment('燃料种类');
			// 排放依据标准
			$table->string('paifangyijubiaozhun')->nullable()->comment('排放依据标准');
			// 轴数
			$table->string('zhoushu')->nullable()->comment('轴数');
			// 轴距
			$table->string('zhouju')->nullable()->comment('轴距');
			// 轴荷
			$table->string('zhouhe')->nullable()->comment('轴荷');
			// 弹簧片数
			$table->string('danhuangpianshu')->nullable()->comment('弹簧片数');
			// 轮胎数
			$table->string('luntaishu')->nullable()->comment('轮胎数');
			// 轮胎规格
			$table->string('luntaiguige')->nullable()->comment('轮胎规格');
			// 接近离去角
			$table->string('jiejinliqujiao')->nullable()->comment('接近离去角');
			// 前悬后悬
			$table->string('qianxuanhouxuan')->nullable()->comment('前悬后悬');
			// 前轮距
			$table->string('qianlunju')->nullable()->comment('前轮距');
			// 后轮距
			$table->string('houlunju')->nullable()->comment('后轮距');
			// 识别代号
			$table->string('shibiedaihao')->nullable()->comment('识别代号');
			// 整车长
			$table->string('zhengchechang')->nullable()->comment('整车长');
			// 整车宽
			$table->string('zhengchekuan')->nullable()->comment('整车宽');
			// 整车高
			$table->string('zhengchegao')->nullable()->comment('整车高');
			// 货箱长
			$table->string('huoxiangchang')->nullable()->comment('货箱长');
			// 货箱宽
			$table->string('huoxiangkuan')->nullable()->comment('货箱宽');
			// 货箱高
			$table->string('huoxianggao')->nullable()->comment('货箱高');
			// 最高车速
			$table->string('zuigaochesu')->nullable()->comment('最高车速');
			// 额定载客
			$table->string('edingzaike')->nullable()->comment('额定载客');
			// 驾驶数准乘人数
			$table->string('jiashishizhunchengrenshu')->nullable()->comment('驾驶数准乘人数');
			// 转向形式
			$table->string('zhuanxiangxingshi')->nullable()->comment('转向形式');
			// 准拖车总质量
			$table->string('zhuntuoguachezongzhiliang')->nullable()->comment('准拖车总质量');
			// 载质量利用系数
			$table->string('zaizhiliangliyongxishu')->nullable()->comment('载质量利用系数');
			// 半挂车鞍座最大承载质量
			$table->string('banguacheanzuozuidachengzaizhiliang')->nullable()->comment('半挂车鞍座最大承载质量');
			// 企业名称
			$table->string('qiyemingcheng')->nullable()->comment('企业名称');
			// 企业地址
			$table->text('qiyedizhi')->nullable()->comment('企业地址');
			// 电话号码
			$table->string('dianhuahaoma')->nullable()->comment('电话号码');
			// 传真号码
			$table->string('chuanzhenhaoma')->nullable()->comment('传真号码');
			// 邮政编码
			$table->string('youzhengbianma')->nullable()->comment('邮政编码');
			// 底盘1
			$table->string('dipan1')->nullable()->comment('底盘1');
			// 底盘2
			$table->string('dipan2')->nullable()->comment('底盘2');
			// 底盘3
			$table->string('dipan3')->nullable()->comment('底盘3');
			// 底盘4
			$table->string('dipan4')->nullable()->comment('底盘4');
			// 发送机型号
			$table->string('fadongjixinghao')->nullable()->comment('发送机型号');
			// 发动机生产企业
			$table->string('fadongjishengchanqiye')->nullable()->comment('发动机生产企业');
			// 发动机商标
			$table->string('fadongjishangbiao')->nullable()->comment('发动机商标');
			// 排量
			$table->string('pailiang')->nullable()->comment('排量');
			// 功率
			$table->string('gonglv')->nullable()->comment('功率');
			// 备注
			$table->text('beizhu')->nullable()->comment('备注');
		});
		echo "raw_data create\r\n";
	}


	// 初始化列表页
	public static function analyse()
	{
		// chunk分块处理每100条数据
		Capsule::table('url_detail')->orderBy('id')->chunk(1000,function($datas){
			// 日志操作类
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECTPATH.'down/fourstep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 保存文件名
		    	$file = PROJECTPATH.'down/url_detail/'.$data->file_path.'/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (is_file($file))
		    	{
		    		$temp = file_get_contents($file);
					// 创建dom对象
					$dom = HtmlDomParser::str_get_html($temp);
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
								$k = pinyin::to(trim($article->find('.t',0)->plaintext));
								$v = $article->find('.t',0)->next_sibling()->plaintext;
								$temp[$k] = $v;
							}
							// 有时候tr只有一个.t的td,排除non-object的exception
							if($article->find('.t',1))
							{
								$k = pinyin::to(trim($article->find('.t',1)->plaintext));
								$v = $article->find('.t',1)->next_sibling()->plaintext;
								$temp[$k] = $v;
							}
						}
						else
						{
							// 处理发动机一行
							// 发动机型号
							$k = pinyin::to(trim($article->find('.f1',0)->plaintext)); 
							$v = $article->find('.f',0)->next_sibling()->children(0)->innertext;
							$temp[$k] = $v;
							// 发动机生产企业
							$k = pinyin::to(trim($article->find('.f2',0)->plaintext));
							$v = $article->find('.f',0)->next_sibling()->children(1)->innertext;
							$temp[$k] = $v;
							// 发动机商标
							$k = pinyin::to(trim($article->find('.f3',0)->plaintext));
							$v = $article->find('.f',0)->next_sibling()->children(2)->innertext;
							$temp[$k] = $v;
							// 排量
							$k = pinyin::to(trim($article->find('.f3',0)->next_sibling()->plaintext));
							$v = $article->find('.f',0)->next_sibling()->children(3)->innertext;
							$temp[$k] = $v;
							// 功率
							$k = pinyin::to(trim($article->find('.f',0)->last_child()->plaintext));
							$v = $article->find('.f',0)->next_sibling()->children(4)->innertext;
							$temp[$k] = $v;
						}
					}
					// 加入批次路径
					$temp['picilujing'] = $data->file_path;
					// 加入当前页面url以供随机查询校验
					$temp['yemiandizhi'] = $data->company_url;
					// 转换字符编码
					foreach ($temp as $k => $v)
					{
						$v = mb_convert_encoding($v, "UTF-8", "gb2312");
						$temp[$k] = $v;
					}
					// 插入记录
					Capsule::table('raw_data')->insert($temp);
					// 清理内存防止内存泄漏
					$dom->clear();
					// 记录成功
				    $LibFile->WriteData($logFile, 4, $data->file_path.'/'.$data->id.'.html'.'解析完成！');
					echo $data->file_path.'/'.$data->id.'.html'." analyse ok!\r\n";
			    }
		    }
		});
	}	
}
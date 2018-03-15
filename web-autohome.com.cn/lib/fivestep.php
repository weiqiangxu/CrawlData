<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;
use Illuminate\Database\Schema\Blueprint;

/**
  * 车型详情
  * @author xu
  * @copyright 2018/01/29
  */
class fivestep{


	// 下载
	public static function car_down()
	{
		// 下载所有的model页面
		Capsule::table('model_detail')->where('status','wait')->orderBy('id')->chunk(10,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'model_detail', 0777, true);
			// 并发请求
		    $guzzle = new guzzle();
		    $guzzle->poolRequest('model_detail',$datas);
		});
	}

	// 分析
	public static function car_analyse()
	{

		// 解析
		Capsule::table('model_detail')->where('status','completed')->orderBy('id')->chunk(10,function($datas){
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 存储文件
		    	$file = PROJECT_APP_DOWN.'model_detail/'.$data->id.'.html';

				//获取输出className的script代码
				preg_match_all('/<script>(.*?)<\/script>/', file_get_contents($file), $matches);
				// 添加打印
				$str = preg_replace('/\$InsertRule\$\s+\(\$index\$,\s+\$item\$\)\s*{/','$InsertRule$($index$, $item$){console.log($item$);',current($matches[1]));
				file_put_contents(PROJECT_APP_DOWN.'javascript.js', $str.' phantom.exit();');

				exec(APP_PATH.'/bin/phantomjs '.PROJECT_APP_DOWN.'javascript.js > '.PROJECT_APP_DOWN.'javascript.txt', $out, $status);

				// keyLink
				preg_match_all('/var\s*keyLink(.*?};)/', file_get_contents($file), $matches);
				$keyLink = current($matches[0]);

				// config
				preg_match_all('/var\s*config(.*?};)/', file_get_contents($file), $matches);
				$config = current($matches[0]);
				
				// option
				preg_match_all('/var\s*option(.*?};)/', file_get_contents($file), $matches);
				$option = current($matches[0]);

				// color
				preg_match_all('/var\s*color(.*?};)/', file_get_contents($file), $matches);
				$color = current($matches[0]);

				// innerColor
				preg_match_all('/var\s*innerColor(.*?};)/', file_get_contents($file), $matches);
				$innerColor = current($matches[0]);


		    	// 是否存在
		    	if (file_exists($file))
		    	{


		    		echo 'get it';die;











					if($dom = HtmlDomParser::str_get_html(file_get_contents($file)))
					{

						// 开启事务
						Capsule::beginTransaction();

						$temp = array();
						// 入库基本信息表
						$temp = array(
							'brand' => $data->brand,
							'subbrand' => $data->subbrand,
							'series' => $data->series,
							'model' => $data->model,
							'md5_url' => $data->md5_url,
							'url' => $data->url
						);
						// 厂商
						$temp['changshang'] = isset($test['218'])?$test['218']:'';
						// 级别
						$temp['jibei'] = isset($test['220'])?$test['220']:'';
						// 整车质保
						$temp['zhengchezhibao'] = isset($test['274'])?$test['274']:'';

						// 能源类型
						$temp['nengyuanleixing'] = isset($test['能源类型'])?$test['能源类型']:'';
						// 上市时间
						$temp['shangshishijian'] = isset($test['上市'])?$test['上市']:'';
						// 最大功率（kw）
						$temp['zuidagonglv'] = isset($test['295'])?$test['295']:'';
						// 最大扭矩（N·m）
						$temp['zuidaniuju'] = isset($test['571'])?$test['571']:'';
						// 发动机
						$temp['faodngji'] = isset($test['555'])?$test['555']:'';
						// 变速箱
						$temp['biansuxiang'] = isset($test['变速箱'])?$test['变速箱']:'';
						// 长*宽*高
						$temp['changkuangao'] = isset($test['222'])?$test['222']:'';
						// 车身结构
						$temp['cheshenjiegou'] = isset($test['281'])?$test['281']:'';
						// 最高车速
						$temp['zuigaochesu'] = isset($test['267'])?$test['267']:'';
						// 官方0-100km/h加速(s)
						$temp['guanfangjiasu'] = isset($test['225'])?$test['225']:'';
						// 实测0-100km/s加速
						$temp['shicejiasu'] = isset($test['272'])?$test['272']:'';
						// 实测100km/s-o制动(m)
						$temp['shicezhidong'] = isset($test['273'])?$test['273']:'';
						// 实测离地间隙（mm）
						$temp['shicelidijianxi'] = isset($test['306'])?$test['306']:'';
						// 工信部综合油耗（L/100km）
						$temp['gongxinbuyouhao'] = isset($test['271'])?$test['271']:'';
						// 实测油耗(L/100km)
						$temp['shiceyouhao'] = isset($test['243'])?$test['243']:'';

						// car_basic
						$empty = Capsule::table('car_basic')->where('md5_url',$data->md5_url)->get()->isEmpty();
						if($empty) $car_id = Capsule::table('car_basic')->insertGetId($temp);
						

						// 入库车身信息表
						$temp = array('car_id' => $car_id);
						// 长度
						$temp['cheshenchangdu'] = isset($test['275'])?$test['275']:'';
						// 宽度
						$temp['cheshenkuandu'] = isset($test['276'])?$test['276']:'';
						// 高度
						$temp['cheshengaodu'] = isset($test['277'])?$test['277']:'';
						// 轴距
						$temp['cheshenzhouju'] = isset($test['132'])?$test['132']:'';
						// 前轮距
						$temp['cheshenqianlunju'] = isset($test['278'])?$test['278']:'';
						// 后轮距
						$temp['cheshenhoulunju'] = isset($test['638'])?$test['638']:'';
						// 最小离地间隙
						$temp['cheshenzuixiaolidijianxi'] = isset($test['279'])?$test['279']:'';
						// 车身结构
						$temp['cheshencheshenjiegou'] = isset($test['281'])?$test['281']:'';
						// 车门数
						$temp['cheshenchemenshu'] = isset($test['282'])?$test['282']:'';
						// 座位数
						$temp['cheshenzuoweishu'] = isset($test['283'])?$test['283']:'';
						// 油箱容积
						$temp['cheshenyouxiangrongji'] = isset($test['284'])?$test['284']:'';
						// 行李箱容积
						$temp['xinglixiangrongji'] = isset($test['285'])?$test['285']:'';
						// 整备质量（kg）
						$temp['zhengbeizhiliang'] = isset($test['280'])?$test['280']:'';

						// car_body
						$empty = Capsule::table('car_body')->where('car_id',$car_id)->get()->isEmpty();
						Capsule::table('car_body')->insert(array_map('trim',$temp));

						// 发动机信息
						$temp = array('car_id' => $car_id);
						// 供油方式
						$temp['gongyoufangshi'] = isset($test['574'])?$test['574']:'';
						// 缸盖材料
						$temp['ganggaicailiao'] = isset($test['575'])?$test['575']:'';
						// 缸体材料
						$temp['gangticailiao'] = isset($test['576'])?$test['576']:'';
						// 环保标准
						$temp['huanbaobiaozhun'] = isset($test['577'])?$test['577']:'';
						// 进气形式
						$temp['jingqixingshi'] = isset($test['640'])?$test['640']:'';

						// 型号
						$temp['fadongjixinghao'] = isset($test['570'])?$test['570']:'';
						// 排量ml
						$temp['pailiangml'] = isset($test['287'])?$test['287']:'';
						// 排量L
						$temp['pailiangl'] = isset($test['(L)'])?$test['(L)']:'';
						// 气缸排列形式
						$temp['qigangpailiexingshi'] = isset($test['289'])?$test['289']:'';
						// 气缸数
						$temp['qigangshu'] = isset($test['290'])?$test['290']:'';
						// 每钢气门数
						$temp['meigangqimenshu'] = isset($test['291'])?$test['291']:'';
						// 压缩比
						$temp['yasuobi'] = isset($test['182'])?$test['182']:'';
						// 配气结构
						$temp['peiqijiegou'] = isset($test['641'])?$test['641']:'';
						// 缸径
						$temp['gangjing'] = isset($test['181'])?$test['181']:'';
						// 行程
						$temp['xingcheng'] = isset($test['293'])?$test['293']:'';
						// 最大马力
						$temp['zuidamali'] = isset($test['294'])?$test['294']:'';
						// 发动机最大功率
						$temp['fadongjizuidagonglv'] = isset($test['295'])?$test['295']:'';
						// 最大功率转速
						$temp['zuidagonglvzhuansu'] = isset($test['296'])?$test['296']:'';
						// 发动机最大扭距
						$temp['fadongjizuidaniuju'] = isset($test['571'])?$test['571']:'';
						// 最大扭距转数
						$temp['zuidaniujuzhuansu'] = isset($test['642'])?$test['642']:'';
						// 特有技术
						$temp['teyoujishu'] = isset($test['643'])?$test['643']:'';
						// 燃料形式
						$temp['ranliaoxingshi'] = isset($test['572'])?$test['572']:'';
						// 燃油标号
						$temp['ranyoubianhao'] = isset($test['573'])?$test['573']:'';
						// car_engine
						$empty = Capsule::table('car_engine')->where('car_id',$car_id)->get()->isEmpty();

						Capsule::table('car_engine')->insert(array_map('trim', $temp));


						// 变速箱+底盘转向+车轮制动
						$temp = array('car_id' => $car_id);
						// 底盘驱动方式
						$temp['dipanqudongfangshi'] = isset($test['395'])?$test['395']:'';
						// 前悬挂类型
						$temp['qianxuangualeixing'] = isset($test['578'])?$test['578']:'';
						// 后悬挂类型
						$temp['houxuangualeixing'] = isset($test['579'])?$test['579']:'';
						// 助力类型
						$temp['zhulileixing'] = isset($test['510'])?$test['510']:'';
						// 车体结构
						$temp['chetijiegou'] = isset($test['223'])?$test['223']:'';

						// 档位个数
						$temp['dangweigeshu'] = isset($test['559'])?$test['559']:'';
						// 变速箱类型
						$temp['biansuxiangleixing'] = isset($test['221'])?$test['221']:'';
						// 简称
						$temp['biansuxiangjiancheng'] = isset($test['1072'])?$test['1072']:'';
						// 前制动器类型
						$temp['qianzhidongqileixing'] = isset($test['511'])?$test['511']:'';
						// 后制动器类型
						$temp['houzhidongqileixing'] = isset($test['512'])?$test['512']:'';
						// 驻车制动类型
						$temp['zhuchezhidongleixing'] = isset($test['513'])?$test['513']:'';
						// 前轮胎规格
						$temp['qianluntaiguige'] = isset($test['580'])?$test['580']:'';
						// 后轮胎规格
						$temp['houluntaiguige'] = isset($test['581'])?$test['581']:'';
						// 备胎规格
						$temp['beitaiguige'] = isset($test['515'])?$test['515']:'';
						// 主副驾座安全气囊
						$temp['zhufujiazuoanquanqinang'] = isset($test['1082'])?$test['1082']:'';
						// 前后侧拍气囊
						$temp['qianhoucepaiqinang'] = isset($test['421'])?$test['421']:'';
						// 前后排头部气囊
						$temp['qianhoupaitoubuqinang'] = isset($test['422'])?$test['422']:'';
						// 膝部气囊
						$temp['xibuqinang'] = isset($test['423'])?$test['423']:'';
						// 胎压监测装置
						$temp['taiyajiancezhuangzhi'] = isset($test['551'])?$test['551']:'';
						// 零胎压继续行驶
						$temp['lingtaiyajixuxingshi'] = isset($test['424'])?$test['424']:'';
						// 安全带维系提示
						$temp['anquandaiweixitishi'] = isset($test['552'])?$test['552']:'';
						// 儿童座椅接口
						$temp['ertongzuoyijiekou'] = isset($test['1084'])?$test['1084']:'';
						// abs防抱死
						$temp['absfangbaosi'] = isset($test['110'])?$test['110']:'';
						// 制动力分配
						$temp['zhidonglifenpei'] = isset($test['125'])?$test['125']:'';
						// 刹车辅助
						$temp['shachefuzhu'] = isset($test['437'])?$test['437']:'';
						// 牵引力控制
						$temp['qianyinlikongzhi'] = isset($test['438'])?$test['438']:'';
						// 车身稳定控制
						$temp['cheshenwendingkongzhi'] = isset($test['109'])?$test['109']:'';
						// 并线辅助
						$temp['bingxianfuzhu'] = isset($test['426'])?$test['426']:'';
						// 车道偏离预警
						$temp['chedaopianliyujing'] = isset($test['788'])?$test['788']:'';
						// 主被动安全系统
						$temp['zhubeidonganquanxitong'] = isset($test['436'])?$test['436']:'';
						// 夜视系统
						$temp['yeshixitong'] = isset($test['637'])?$test['637']:'';
						// 疲劳驾驶
						$temp['pilaojiashi'] = isset($test['疲劳提示'])?$test['疲劳提示']:'';

						// car_gearbox
						$empty = Capsule::table('car_gearbox')->where('car_id',$car_id)->get()->isEmpty();
						Capsule::table('car_gearbox')->insert(array_map('trim',$temp));


						// 操控配置和防盗配置car_configure
						$temp = array('car_id' => $car_id);
						// 前后驻车雷达
						$temp['qianhouzhucheleida'] = isset($test['1086'])?$test['1086']:'';
						// 方向盘调节
						$temp['fangxiangpantiaojie'] = isset($test['1085'])?$test['1085']:'';
						// 倒车视屏影像
						$temp['daocheshipingyingxiang'] = isset($test['448'])?$test['448']:'';
						// 可变转向化
						$temp['kebianzhuanxianghua'] = isset($test['409'])?$test['409']:'';
						// 无钥匙进入系统
						$temp['wuyaoshijinruxitong'] = isset($test['1066'])?$test['1066']:'';

						/*********************************************************************************************************/
						// 全景摄像头
						$temp['quanjingshexiangtou'] = isset($test['473'])?$test['473']:'';
						// 低速巡航
						$temp['dingsuxunhang'] = isset($test['445'])?$test['445']:'';
						// 自适应巡航
						$temp['zishiyingxunhang'] = isset($test['446'])?$test['446']:'';
						// 自动泊车
						$temp['zidongboche'] = isset($test['472'])?$test['472']:'';
						// 发动机启停
						$temp['fadongjiqiting'] = isset($test['334'])?$test['334']:'';
						// 自动驾驶
						$temp['zidongjiashi'] = isset($test['自动技术'])?$test['自动技术']:'';
						// 上坡辅助
						$temp['shangpofuzhu'] = isset($test['上坡辅助'])?$test['上坡辅助']:'';
						// 自动驻车
						$temp['zidongzhuche'] = isset($test['363'])?$test['363']:'';
						// 陡坡缓降
						$temp['doupohuanjiang'] = isset($test['138'])?$test['138']:'';
						// 可变悬挂
						$temp['kebianxuangua'] = isset($test['399'])?$test['399']:'';
						// 空气悬挂
						$temp['kongqixuangua'] = isset($test['167'])?$test['167']:'';
						// 电磁感应悬挂
						$temp['dianciganyingxuangua'] = isset($test['感应'])?$test['感应']:'';
						// 前桥差速器
						$temp['qianqiaochasuqi'] = isset($test['975'])?$test['975']:'';
						// 中央差速器
						$temp['zhongyangchasuqi'] = isset($test['976'])?$test['976']:'';
						// 后桥差速器
						$temp['houqiaochasuqi'] = isset($test['977'])?$test['977']:'';
						// 整车主动转向
						$temp['zhengchezhudongzhuanxiang'] = isset($test['404'])?$test['404']:'';
						// 电动天窗
						$temp['diandongtianchuang'] = isset($test['583'])?$test['583']:'';
						// 全景天窗
						$temp['quanjingtianchuang'] = isset($test['584'])?$test['584']:'';
						// 多天窗
						$temp['duotianchuang'] = isset($test['多'])?$test['多']:'';
						// 运动外观套件
						$temp['yundongwaiguantaojian'] = isset($test['585'])?$test['585']:'';
						// 铝合金轮圈
						$temp['lvhejinlunquan'] = isset($test['525'])?$test['525']:'';
						// 电动吸合门
						$temp['diandongxihemen'] = isset($test['443'])?$test['443']:'';
						// 侧滑门
						$temp['cehuamen'] = isset($test['1122'])?$test['1122']:'';
						// 电动后备箱
						$temp['diandonghoubeixiang'] = isset($test['452'])?$test['452']:'';
						// 感应后备箱
						$temp['ganyinghoubeixiang'] = isset($test['感应后备厢'])?$test['感应后备厢']:'';
						// 车顶行李架
						$temp['chedingxinglijia'] = isset($test['车顶行李架'])?$test['车顶行李架']:'';
						// 发动机电子防盗
						$temp['fadongjidianzifangdao'] = isset($test['481'])?$test['481']:'';
						// 车内中控锁
						$temp['cheneizhongkongsuo'] = isset($test['558'])?$test['558']:'';
						// 遥控钥匙
						$temp['yaokongyaoshi'] = isset($test['582'])?$test['582']:'';
						// 无钥匙启动系统
						$temp['wuyaoshiqidongxitong'] = isset($test['431'])?$test['431']:'';
						// 远程启动
						$temp['yuanchengqidong'] = isset($test['远程启动'])?$test['远程启动']:'';
						// 皮质方向盘
						$temp['pizhifangxiangpan'] = isset($test['皮质方向盘'])?$test['皮质方向盘']:'';
						// 方向盘电动
						$temp['fangxiangpandiandong'] = isset($test['589'])?$test['589']:'';
						// 多功能方向盘
						$temp['duogongnengfangxiangpan'] = isset($test['444'])?$test['444']:'';
						// 方向盘换挡
						$temp['fangxiangpanhuandang'] = isset($test['468'])?$test['468']:'';
						// 方向盘加热
						$temp['fangxiangpanjiare'] = isset($test['1064'])?$test['1064']:'';
						// 方向盘记忆
						$temp['fangxiangpanjiyi'] = isset($test['方向盘记忆'])?$test['方向盘记忆']:'';
						// 行车电脑显示屏
						$temp['xingchediannaoxianshiping'] = isset($test['590'])?$test['590']:'';
						// 全液晶仪表盘
						$temp['quanyejingyibiaopan'] = isset($test['全液晶仪表盘'])?$test['全液晶仪表盘']:'';
						// 互动抬头数字显示
						$temp['hudtaitoushuzixianshi'] = isset($test['471'])?$test['471']:'';
						// 内置行车记录仪
						$temp['neizhixingchejiluyi'] = isset($test['内置行车记录仪'])?$test['内置行车记录仪']:'';
						// 主动降噪
						$temp['zhudongjiangzao'] = isset($test['降噪'])?$test['降噪']:'';
						// 手机无线充电
						$temp['shoujiwuxianchongdian'] = isset($test['手机无线'])?$test['手机无线']:'';
										
						$empty = Capsule::table('car_configure')->where('car_id',$car_id)->get()->isEmpty();
						Capsule::table('car_configure')->insert(array_map('trim',$temp));


						// 座椅配置和多媒体配置
						$temp = array('car_id' => $car_id);
						// 座椅材质
						$temp['zuoyicaizhi'] = isset($test['座椅'])?$test['座椅']:'';
						// 后排座椅放倒方式
						$temp['houpaizuoyifangdaofangshi'] = isset($test['1091'])?$test['1091']:'';
						// 前后中央扶手
						$temp['qianhouzhongyangfushou'] = isset($test['1092'])?$test['1092']:'';
						// 运动座椅风格
						$temp['yundongfenggezuoyi'] = isset($test['592'])?$test['592']:'';
						// 腰部支撑调节
						$temp['yaobuzhichengtiaojie'] = isset($test['449'])?$test['449']:'';
						// 手机互联映射
						$temp['shoujihulianyingshe'] = isset($test['手机互联/映射'])?$test['手机互联/映射']:'';

						/*********************************************************************************************************/
						// 座椅高低调节 
						$temp['zuoyigaoditiaojie'] = isset($test['639'])?$test['639']:'';
						// 肩部支撑调节
						$temp['jianbuzhichengtiaojie'] = isset($test['593'])?$test['593']:'';
						// 主副驾座电动调节
						$temp['zhufujiazuodiandongtiaojie'] = isset($test['1087'])?$test['1087']:'';
						// 第二排角度调节
						$temp['dierpaijiaodutiaojie'] = isset($test['595'])?$test['595']:'';
						// 第二排座椅移动
						$temp['dierpaizuoyiyidong'] = isset($test['596'])?$test['596']:'';
						// 后排座椅电动
						$temp['houpaizuoyidiandong'] = isset($test['597'])?$test['597']:'';
						// 副驾驶后排可调节
						$temp['fujiashihoupaiketiaojie'] = isset($test['副位可按钮'])?$test['副位可按钮']:'';
						// 电动座椅记忆
						$temp['diandongzuoyijiyi'] = isset($test['598'])?$test['598']:'';
						// 前后座椅加热
						$temp['qianhouzuoyijiare'] = isset($test['1088'])?$test['1088']:'';
						// 前后座椅通风
						$temp['qianhouzuoyitongfeng'] = isset($test['1089'])?$test['1089']:'';
						// 前后座椅按摩
						$temp['qianhouzuoyianmo'] = isset($test['1090'])?$test['1090']:'';
						// 第二排独立座椅
						$temp['dierpaidulizuoyi'] = isset($test['第二排座椅'])?$test['第二排座椅']:'';
						// 第三排座椅
						$temp['disanpaizuoyi'] = isset($test['603'])?$test['603']:'';
						// 后排杯架
						$temp['houpaibeijia'] = isset($test['606'])?$test['606']:'';
						// 加热制冷杯架
						$temp['jiarezhilengbeijia'] = isset($test['可/制冷杯架'])?$test['可/制冷杯架']:'';
						// gps导航服务
						$temp['gpsdaohangfuwu'] = isset($test['607'])?$test['607']:'';
						// 定位互动
						$temp['dingweihudong'] = isset($test['455'])?$test['455']:'';
						// 中控台彩色大屏
						$temp['zhongkongtaicaisedaping'] = isset($test['608'])?$test['608']:'';
						// 中控台彩色大屏尺寸
						$temp['zhongkongtaicaisedapingchicun'] = isset($test['中控台彩色大屏尺寸'])?$test['中控台彩色大屏尺寸']:'';
						// 中控液晶屏分屏显示
						$temp['zhongkongyejingpingfenpingxianshi'] = isset($test['464'])?$test['464']:'';
						// 蓝牙车载电话
						$temp['lanyachezaidianhua'] = isset($test['609'])?$test['609']:'';
						// 车联网
						$temp['chelianwang'] = isset($test['车联网'])?$test['车联网']:'';
						// 车载电视
						$temp['chezaidianshi'] = isset($test['610'])?$test['610']:'';
						// 后排液晶屏
						$temp['houpaiyejingping'] = isset($test['611'])?$test['611']:'';
						// 220v/230v电源
						$temp['dianyuan'] = isset($test['220V/230V电源'])?$test['220V/230V电源']:'';
						// 音源接口
						$temp['yinyuanjiekou'] = isset($test['音源接口'])?$test['音源接口']:'';
						// cddvd
						$temp['cddvd'] = isset($test['CD/DVD'])?$test['CD/DVD']:'';
						
						// 扬声器品牌
						$temp['yangshengqipinpai'] = isset($test['1212'])?$test['1212']:'';
						// 扬声器数量
						$temp['yangshengqishuliang'] = isset($test['618'])?$test['618']:'';

						$empty = Capsule::table('car_chair')->where('car_id',$car_id)->get()->isEmpty();
						Capsule::table('car_chair')->insert(array_map('trim',$temp));

					

						$temp = array('car_id' => $car_id);
						// 近光灯
						$temp['jinguangdeng'] = isset($test['近光灯'])?$test['近光灯']:'';
						// 远光灯
						$temp['yuanguangdeng'] = isset($test['远光灯'])?$test['远光灯']:'';
						// 前后电动车窗
						$temp['qianhoudiandongchechuang'] = isset($test['622'])?$test['622']:'';
						// 外观颜色
						$temp['waiguanyanse'] = isset($test['外观颜色'])?$test['外观颜色']:'';
						// 内饰颜色
						$temp['neishiyanse'] = isset($test['内饰颜色'])?$test['内饰颜色']:'';

						// 防紫外线/隔热玻璃
						$temp['fangziwaixiangereboli'] = isset($test['624'])?$test['624']:'';

						/*********************************************************************************************************/
						// 日间行车灯
						$temp['rijianxingchedeng'] = isset($test['LED日间行车灯'])?$test['LED日间行车灯']:'';
						// 自适应远近光
						$temp['zishiyingyuanjinguang'] = isset($test['自应远近光'])?$test['自应远近光']:'';
						// 自动头灯
						$temp['zidongtoudeng'] = isset($test['441'])?$test['441']:'';
						// 转向辅助灯
						$temp['zhuanxiangfuzhudeng'] = isset($test['1161'])?$test['1161']:'';
						// 转向头灯
						$temp['zhuanxiangtoudeng'] = isset($test['转向头灯'])?$test['转向头灯']:'';
						// 前雾灯
						$temp['qianwudeng'] = isset($test['619'])?$test['619']:'';
						// 大灯高度可调
						$temp['dadenggaoduketiao'] = isset($test['620'])?$test['620']:'';
						// 大灯清洗装置
						$temp['dadengqingxizhuangzhi'] = isset($test['621'])?$test['621']:'';
						// 车内氛围灯
						$temp['cheneifenweideng'] = isset($test['453'])?$test['453']:'';
						// 车窗一键升降
						$temp['chechuangyijianshengjiang'] = isset($test['车窗一键降'])?$test['车窗一键降']:'';
						// 车窗防夹手功能
						$temp['chechuangfangjiashou'] = isset($test['623'])?$test['623']:'';
						// 后视镜电动调节
						$temp['houshijingdiandongtiaojie'] = isset($test['625'])?$test['625']:'';
						// 后视镜加热
						$temp['houshijingjiare'] = isset($test['626'])?$test['626']:'';
						// 后视镜自动防眩目
						$temp['houshijingzidongfangxuanmu'] = isset($test['1095'])?$test['1095']:'';
						// 流媒体车内后视镜
						$temp['liumeiticheneihoushijing'] = isset($test['流媒体车内后视镜'])?$test['流媒体车内后视镜']:'';
						// 后视镜电动折叠
						$temp['houshijingdiandongzhedie'] = isset($test['628'])?$test['628']:'';
						// 后视镜记忆
						$temp['houshijingjiyi'] = isset($test['629'])?$test['629']:'';
						// 后风挡遮阳帘
						$temp['houfengdangzheyanglian'] = isset($test['630'])?$test['630']:'';
						// 后排侧遮阳帘
						$temp['houpaicezheyanglian'] = isset($test['631'])?$test['631']:'';
						// 后排侧隐私玻璃
						$temp['houpaiceyinsiboli'] = isset($test['1063'])?$test['1063']:'';
						// 遮阳板化妆镜
						$temp['zheyangbanhuazhuangjing'] = isset($test['632'])?$test['632']:'';
						// 后雨刷
						$temp['houyushua'] = isset($test['633'])?$test['633']:'';
						// 感应雨刷
						$temp['ganyingyushua'] = isset($test['454'])?$test['454']:'';
						// 空调控制方式
						$temp['kongtiaokongzhifangshi'] = isset($test['1097'])?$test['1097']:'';
						// 后排独立空调
						$temp['houpaidulikongtiao'] = isset($test['459'])?$test['459']:'';
						// 后座出风口
						$temp['houzuochufengkou'] = isset($test['634'])?$test['634']:'';
						// 温度分区控制
						$temp['wendufenqukongzhi'] = isset($test['463'])?$test['463']:'';
						// 花粉过滤
						$temp['huafenguolv'] = isset($test['635'])?$test['635']:'';
						// 车载空气净化器
						$temp['chezaikongqijinghuaqi'] = isset($test['车载净化器'])?$test['车载净化器']:'';
						// 车载冰箱
						$temp['chezaibingxiang'] = isset($test['636'])?$test['636']:'';

						$empty = Capsule::table('car_lighting')->where('car_id',$car_id)->get()->isEmpty();
						Capsule::table('car_lighting')->insert(array_map('trim',$temp));

						Capsule::commit();

				        // 更改SQL语句
				        Capsule::table('model_detail')->where('id', $data->id)->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
				        echo 'model_detail '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});
	}
}
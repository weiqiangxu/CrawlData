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

	// car
	public static function car()
	{

		$wait_count = Capsule::table('model_detail')->where('status','wait')->count();

		// 下载页面
		while ($wait_count > 0) {
			// 每次读10条
			$datas = Capsule::table('model_detail')->where('status','wait')->where('downing',0)->limit(20)->get();

			foreach ($datas as $data) {
				// 标记为正在下载
				 Capsule::table('model_detail')->where('id', $data->id)->update(['downing' =>1]);
			}
			foreach ($datas as $data) {
				$guzzle = new guzzle();
		    	$guzzle->phantomjsDown('model_detail',$data);
			}
			
			$wait_count = Capsule::table('model_detail')->where('status','wait')->count();

			foreach ($datas as $data) {
				// 不再下载状态
				 Capsule::table('model_detail')->where('id', $data->id)->update(['downing' =>0]);
			}
		}


// 解析
Capsule::table('model_detail')->where('status','completed')->orderBy('id')->chunk(10,function($datas){
	// 循环块级结果
    foreach ($datas as $data)
    {
    	// 解析页面
    	$file = PROJECT_APP_DOWN.'model_detail/'.$data->id.'.html';
    	// 判定是否已经存在且合法
    	if (file_exists($file))
    	{

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
				'md5_url' => $data->md5_url
			);
		// 厂商
		if($dom->find("#tr_0",0)){   }
		// 级别
		if($dom->find("#tr_1",0)){   }
		// 整车质保
		if($dom->find("#tr_16",0)){  }
		// 能源类型
		if($dom->find("#tr_2",0)) $temp['nengyuanleixing'] = $dom->find("#tr_2",0)->children(1)->plaintext;
		// 上市时间
		if($dom->find("#tr_3",0)) $temp['shangshishijian'] = $dom->find("#tr_3",0)->children(1)->plaintext;
		// 最大功率（kw）
		if($dom->find("#tr_4",0)) $temp['zuidagonglv'] = $dom->find("#tr_4",0)->children(1)->plaintext;
		// 最大扭矩（N·m）
		if($dom->find("#tr_5",0)) $temp['zuidaniuju'] = $dom->find("#tr_5",0)->children(1)->plaintext;
		// 发动机
		if($dom->find("#tr_6",0)) $temp['faodngji'] = $dom->find("#tr_6",0)->children(1)->plaintext;
		// 变速箱
		if($dom->find("#tr_7",0)) $temp['biansuxiang'] = $dom->find("#tr_7",0)->children(1)->plaintext;
		// 长*宽*高
		if($dom->find("#tr_8",0)) $temp['changkuangao'] = $dom->find("#tr_8",0)->children(1)->plaintext;
		// 车身结构
		if($dom->find("#tr_9",0)) $temp['cheshenjiegou'] = $dom->find("#tr_9",0)->children(1)->plaintext;
		// 最高车速
		if($dom->find("#tr_10",0)) $temp['zuigaochesu'] = $dom->find("#tr_10",0)->children(1)->plaintext;
		// 官方0-100km/h加速(s)
		if($dom->find("#tr_11",0)) $temp['guanfangjiasu'] = $dom->find("#tr_11",0)->children(1)->plaintext;
		// 实测0-100km/s加速
		if($dom->find("#tr_12",0)) $temp['shicejiasu'] = $dom->find("#tr_12",0)->children(1)->plaintext;
		// 实测100km/s-o制动(m)
		if($dom->find("#tr_13",0)) $temp['shicezhidong'] = $dom->find("#tr_13",0)->children(1)->plaintext;
		// 实测离地间隙（mm）
		if($dom->find("#tr_14",0)) $temp['shicelidijianxi'] = $dom->find("#tr_14",0)->children(1)->plaintext;
		// 工信部综合油耗（L/100km）
		if($dom->find("#tr_15",0)) $temp['gongxinbuyouhao'] = $dom->find("#tr_15",0)->children(1)->plaintext;
		// 实测油耗(L/100km)
		if($dom->find("#tr_16",0)) $temp['shiceyouhao'] = $dom->find("#tr_16",0)->children(1)->plaintext;
		// car_basic
		$empty = Capsule::table('car_basic')->where('md5_url',md5($list_url))->get()->isEmpty();
		if($empty) $car_id = Capsule::table('car_basic')->insertGetId($temp);
		

		// 入库车身信息表
		$temp = array('car_id' => $car_id);
		// 长度
		if($dom->find("#tr_18",0)) $temp['cheshenchangdu'] = $dom->find("#tr_18",0)->children(1)->plaintext;
		// 宽度
		if($dom->find("#tr_19",0)) $temp['cheshenkuandu'] = $dom->find("#tr_19",0)->children(1)->plaintext;
		// 高度
		if($dom->find("#tr_20",0)) $temp['cheshengaodu'] = $dom->find("#tr_20",0)->children(1)->plaintext;
		// 轴距
		if($dom->find("#tr_21",0)) $temp['cheshenzhouju'] = $dom->find("#tr_21",0)->children(1)->plaintext;
		// 前轮距
		if($dom->find("#tr_22",0)) $temp['cheshenqianlunju'] = $dom->find("#tr_22",0)->children(1)->plaintext;
		// 后轮距
		if($dom->find("#tr_23",0)) $temp['cheshenhoulunju'] = $dom->find("#tr_23",0)->children(1)->plaintext;
		// 最小离地间隙
		if($dom->find("#tr_24",0)) $temp['cheshenzuixiaolidijianxi'] = $dom->find("#tr_24",0)->children(1)->plaintext;
		// 车身结构
		if($dom->find("#tr_25",0)) $temp['cheshencheshenjiegou'] = $dom->find("#tr_25",0)->children(1)->plaintext;
		// 车门数
		if($dom->find("#tr_26",0)) $temp['cheshenchemenshu'] = $dom->find("#tr_26",0)->children(1)->plaintext;
		// 座位数
		if($dom->find("#tr_27",0)) $temp['cheshenzuoweishu'] = $dom->find("#tr_27",0)->children(1)->plaintext;
		// 油箱容积
		if($dom->find("#tr_28",0)) $temp['cheshenyouxiangrongji'] = $dom->find("#tr_28",0)->children(1)->plaintext;
		// 行李箱容积
		if($dom->find("#tr_29",0)) $temp['xinglixiangrongji'] = $dom->find("#tr_29",0)->children(1)->plaintext;
		// 整备质量（kg）
		if($dom->find("#tr_30",0)) $temp['zhengbeizhiliang'] = $dom->find("#tr_30",0)->children(1)->plaintext;
		// car_body
		$empty = Capsule::table('car_body')->where('car_id',$car_id)->get()->isEmpty();
		Capsule::table('car_body')->insert($temp);


		// 发动机信息
		$temp = array('car_id' => $car_id);
		// 供油方式
		if($dom->find("#tr_50",0)){ }
		// 缸盖材料
		if($dom->find("#tr_51",0)){ }
		// 缸体材料
		if($dom->find("#tr_52",0)){ }
		// 环保标准
		if($dom->find("#tr_53",0)){ }
		// 进气形式
		if($dom->find("#tr_34",0)){ }
		// 型号
		if($dom->find("#tr_31",0)) $temp['fadongjixinghao'] = $dom->find("#tr_31",0)->children(1)->plaintext;
		// 排量ml
		if($dom->find("#tr_32",0)) $temp['pailiangml'] = $dom->find("#tr_32",0)->children(1)->plaintext;
		// 排量L
		if($dom->find("#tr_33",0)) $temp['pailiangl'] = $dom->find("#tr_33",0)->children(1)->plaintext;
		// 气缸排列形式
		if($dom->find("#tr_35",0)) $temp['qigangpailiexingshi'] = $dom->find("#tr_35",0)->children(1)->plaintext;
		// 气缸数
		if($dom->find("#tr_36",0)) $temp['qigangshu'] = $dom->find("#",0)->children(1)->plaintext;
		// 每钢气门数
		if($dom->find("#tr_37",0)) $temp['meigangqimenshu'] = $dom->find("#tr_37",0)->children(1)->plaintext;
		// 压缩比
		if($dom->find("#tr_38",0)) $temp['yasuobi'] = $dom->find("#tr_38",0)->children(1)->plaintext;
		// 配气结构
		if($dom->find("#tr_39",0)) $temp['peiqijiegou'] = $dom->find("#tr_39",0)->children(1)->plaintext;
		// 缸径
		if($dom->find("#tr_40",0)) $temp['gangjing'] = $dom->find("#tr_40",0)->children(1)->plaintext;
		// 行程
		if($dom->find("#tr_41",0)) $temp['xingcheng'] = $dom->find("#tr_41",0)->children(1)->plaintext;
		// 最大马力
		if($dom->find("#tr_42",0)) $temp['zuidamali'] = $dom->find("#tr_42",0)->children(1)->plaintext;
		// 发动机最大功率
		if($dom->find("#tr_43",0)) $temp['fadongjizuidagonglv'] = $dom->find("#tr_43",0)->children(1)->plaintext;
		// 最大功率转速
		if($dom->find("#tr_44",0)) $temp['zuidagonglvzhuansu'] = $dom->find("#tr_44",0)->children(1)->plaintext;
		// 发动机最大扭距
		if($dom->find("#tr_45",0)) $temp['fadongjizuidaniuju'] = $dom->find("#tr_45",0)->children(1)->plaintext;
		// 最大扭距转数
		if($dom->find("#tr_46",0)) $temp['zuidaniujuzhuansu'] = $dom->find("#tr_46",0)->children(1)->plaintext;
		// 特有技术
		if($dom->find("#tr_47",0)) $temp['teyoujishu'] = $dom->find("#tr_47",0)->children(1)->plaintext;
		// 燃料形式
		if($dom->find("#tr_48",0)) $temp['ranliaoxingshi'] = $dom->find("#tr_48",0)->children(1)->plaintext;
		// 燃油标号
		if($dom->find("#tr_49",0)) $temp['ranyoubianhao'] = $dom->find("#tr_49",0)->children(1)->plaintext;
		// car_engine
		$empty = Capsule::table('car_engine')->where('car_id',$car_id)->get()->isEmpty();
		Capsule::table('car_engine')->insert($temp);




		// 变速箱+底盘转向+车轮制动
		$temp = array('car_id' => $car_id);
		// 底盘驱动方式
		if($dom->find("#tr_57",0)) { }
		// 前悬挂类型
		if($dom->find("#tr_58",0)) { }
		// 后悬挂类型
		if($dom->find("#tr_59",0)) { }
		// 助力类型
		if($dom->find("#tr_60",0)) { }
		// 车体结构
		if($dom->find("#tr_61",0)) { }
		// 档位个数
		if($dom->find("#tr_54",0)) $temp['dangweigeshu'] = $dom->find("#tr_54",0)->children(1)->plaintext;
		// 变速箱类型
		if($dom->find("#tr_55",0)) $temp['biansuxiangleixing'] = $dom->find("#tr_55",0)->children(1)->plaintext;
		// 简称
		if($dom->find("#tr_56",0)) $temp['biansuxiangjiancheng'] = $dom->find("#tr_56",0)->children(1)->plaintext;
		// 前制动器类型
		if($dom->find("#tr_62",0)) $temp['qianzhidongqileixing'] = $dom->find("#tr_62",0)->children(1)->plaintext;
		// 后制动器类型
		if($dom->find("#tr_63",0)) $temp['houzhidongqileixing'] = $dom->find("#tr_63",0)->children(1)->plaintext;
		// 驻车制动类型
		if($dom->find("#tr_64",0)) $temp['zhuchezhidongleixing'] = $dom->find("#tr_64",0)->children(1)->plaintext;
		// 前轮胎规格
		if($dom->find("#tr_65",0)) $temp['qianluntaiguige'] = $dom->find("#tr_65",0)->children(1)->plaintext;
		// 后轮胎规格
		if($dom->find("#tr_66",0)) $temp['houluntaiguige'] = $dom->find("#tr_66",0)->children(1)->plaintext;
		// 备胎规格
		if($dom->find("#tr_67",0)) $temp['beitaiguige'] = $dom->find("#tr_67",0)->children(1)->plaintext;
		// 主副驾座安全气囊
		if($dom->find("#tr_200",0)) $temp['zhufujiazuoanquanqinang'] = $dom->find("#tr_200",0)->children(1)->plaintext;
		// 前后侧拍气囊
		if($dom->find("#tr_201",0)) $temp['qianhoucepaiqinang'] = $dom->find("#tr_201",0)->children(1)->plaintext;
		// 前后排头部气囊
		if($dom->find("#tr_202",0)) $temp['qianhoupaitoubuqinang'] = $dom->find("#tr_202",0)->children(1)->plaintext;
		// 膝部气囊
		if($dom->find("#tr_203",0)) $temp['xibuqinang'] = $dom->find("#tr_203",0)->children(1)->plaintext;
		// 胎压监测装置
		if($dom->find("#tr_204",0)) $temp['taiyajiancezhuangzhi'] = $dom->find("#tr_204",0)->children(1)->plaintext;
		// 零胎压继续行驶
		if($dom->find("#tr_205",0)) $temp['lingtaiyajixuxingshi'] = $dom->find("#tr_205",0)->children(1)->plaintext;
		// 安全带维系提示
		if($dom->find("#tr_206",0)) $temp['anquandaiweixitishi'] = $dom->find("#tr_206",0)->children(1)->plaintext;
		// 儿童座椅接口
		if($dom->find("#tr_207",0)) $temp['ertongzuoyijiekou'] = $dom->find("#tr_207",0)->children(1)->plaintext;
		// abs防抱死
		if($dom->find("#tr_208",0)) $temp['absfangbaosi'] = $dom->find("#tr_208",0)->children(1)->plaintext;
		// 制动力分配
		if($dom->find("#tr_209",0)) $temp['zhidonglifenpei'] = $dom->find("#tr_209",0)->children(1)->plaintext;
		// 刹车辅助
		if($dom->find("#tr_210",0)) $temp['shachefuzhu'] = $dom->find("#tr_210",0)->children(1)->plaintext;
		// 牵引力控制
		if($dom->find("#tr_211",0)) $temp['qianyinlikongzhi'] = $dom->find("#tr_211",0)->children(1)->plaintext;
		// 车身稳定控制
		if($dom->find("#tr_212",0)) $temp['cheshenwendingkongzhi'] = $dom->find("#tr_212",0)->children(1)->plaintext;
		// 并线辅助
		if($dom->find("#tr_213",0)) $temp['bingxianfuzhu'] = $dom->find("#tr_213",0)->children(1)->plaintext;
		// 车道偏离预警
		if($dom->find("#tr_214",0)) $temp['chedaopianliyujing'] = $dom->find("#tr_214",0)->children(1)->plaintext;
		// 主被动安全系统
		if($dom->find("#tr_215",0)) $temp['zhubeidonganquanxitong'] = $dom->find("#tr_215",0)->children(1)->plaintext;
		// 夜视系统
		if($dom->find("#tr_216",0)) $temp['yeshixitong'] = $dom->find("#tr_216",0)->children(1)->plaintext;
		// 疲劳驾驶
		if($dom->find("#tr_217",0)) $temp['pilaojiashi'] = $dom->find("#tr_217",0)->children(1)->plaintext;
		// car_gearbox
		$empty = Capsule::table('car_gearbox')->where('car_id',$car_id)->get()->isEmpty();
		Capsule::table('car_gearbox')->insert($temp);



		// 操控配置和防盗配置car_configure
		$temp = array('car_id' => $car_id);
		// 前后驻车雷达
		if($dom->find("#tr_218",0)) { }
		// 方向盘调节
		if($dom->find("#tr_254",0)) { }
		// 倒车视屏影像
		if($dom->find("#tr_219",0)) $temp['daocheshipingyingxiang'] = $dom->find("#tr_219",0)->children(1)->find('p',0)->plaintext;
		// 全景摄像头
		if($dom->find("#tr_220",0)) $temp['quanjingshexiangtou'] = $dom->find("#tr_220",0)->children(1)->plaintext;
		// 低速巡航
		if($dom->find("#tr_221",0)) $temp['dingsuxunhang'] = $dom->find("#tr_221",0)->children(1)->plaintext;
		// 自适应巡航
		if($dom->find("#tr_222",0)) $temp['zishiyingxunhang'] = $dom->find("#tr_222",0)->children(1)->plaintext;
		// 自动泊车
		if($dom->find("#tr_223",0)) $temp['zidongboche'] = $dom->find("#tr_223",0)->children(1)->plaintext;
		// 发动机启停
		if($dom->find("#tr_224",0)) $temp['fadongjiqiting'] = $dom->find("#tr_224",0)->children(1)->plaintext;
		// 自动驾驶
		if($dom->find("#tr_225",0)) $temp['zidongjiashi'] = $dom->find("#tr_225",0)->children(1)->plaintext;
		// 上坡辅助
		if($dom->find("#tr_226",0)) $temp['shangpofuzhu'] = $dom->find("#tr_226",0)->children(1)->plaintext;
		// 自动驻车
		if($dom->find("#tr_227",0)) $temp['zidongzhuche'] = $dom->find("#tr_227",0)->children(1)->plaintext;
		// 陡坡缓降
		if($dom->find("#tr_228",0)) $temp['doupohuanjiang'] = $dom->find("#tr_228",0)->children(1)->plaintext;
		// 可变悬挂
		if($dom->find("#tr_229",0)) $temp['kebianxuangua'] = $dom->find("#tr_229",0)->children(1)->plaintext;
		// 空气悬挂
		if($dom->find("#tr_230",0)) $temp['kongqixuangua'] = $dom->find("#tr_230",0)->children(1)->plaintext;
		// 电磁感应悬挂
		if($dom->find("#tr_231",0)) $temp['dianciganyingxuangua'] = $dom->find("#tr_231",0)->children(1)->plaintext;
		// 可变转向化
		if($dom->find("#tr_232",0)) $temp['kebianzhuanxianghua'] = $dom->find("#tr_232",0)->children(1)->find('p',0)->plaintext;
		// 前桥差速器
		if($dom->find("#tr_233",0)) $temp['qianqiaochasuqi'] = $dom->find("#tr_233",0)->children(1)->plaintext;
		// 中央差速器
		if($dom->find("#tr_234",0)) $temp['zhongyangchasuqi'] = $dom->find("#tr_234",0)->children(1)->plaintext;
		// 后桥差速器
		if($dom->find("#tr_235",0)) $temp['houqiaochasuqi'] = $dom->find("#tr_235",0)->children(1)->plaintext;
		// 整车主动转向
		if($dom->find("#tr_236",0)) $temp['zhengchezhudongzhuanxiang'] = $dom->find("#tr_236",0)->children(1)->plaintext;
		// 电动天窗
		if($dom->find("#tr_237",0)) $temp['diandongtianchuang'] = $dom->find("#tr_237",0)->children(1)->plaintext;
		// 全景天窗
		if($dom->find("#tr_238",0)) $temp['quanjingtianchuang'] = $dom->find("#tr_238",0)->children(1)->plaintext;
		// 多天窗
		if($dom->find("#tr_239",0)) $temp['duotianchuang'] = $dom->find("#tr_239",0)->children(1)->plaintext;
		// 运动外观套件
		if($dom->find("#tr_240",0)) $temp['yundongwaiguantaojian'] = $dom->find("#tr_240",0)->children(1)->plaintext;
		// 铝合金轮圈
		if($dom->find("#tr_241",0)) $temp['lvhejinlunquan'] = $dom->find("#tr_241",0)->children(1)->plaintext;
		// 电动吸合门
		if($dom->find("#tr_242",0)) $temp['diandongxihemen'] = $dom->find("#tr_242",0)->children(1)->plaintext;
		// 侧滑门
		if($dom->find("#tr_243",0)) $temp['cehuamen'] = $dom->find("#tr_243",0)->children(1)->plaintext;
		// 电动后备箱
		if($dom->find("#tr_244",0)) $temp['diandonghoubeixiang'] = $dom->find("#tr_244",0)->children(1)->plaintext;
		// 感应后备箱
		if($dom->find("#tr_245",0)) $temp['ganyinghoubeixiang'] = $dom->find("#tr_245",0)->children(1)->plaintext;
		// 车顶行李架
		if($dom->find("#tr_246",0)) $temp['chedingxinglijia'] = $dom->find("#tr_246",0)->children(1)->plaintext;
		// 发动机电子防盗
		if($dom->find("#tr_247",0)) $temp['fadongjidianzifangdao'] = $dom->find("#tr_247",0)->children(1)->plaintext;
		// 车内中控锁
		if($dom->find("#tr_248",0)) $temp['cheneizhongkongsuo'] = $dom->find("#tr_248",0)->children(1)->plaintext;
		// 遥控钥匙
		if($dom->find("#tr_249",0)) $temp['yaokongyaoshi'] = $dom->find("#tr_249",0)->children(1)->plaintext;
		// 无钥匙启动系统
		if($dom->find("#tr_250",0)) $temp['wuyaoshiqidongxitong'] = $dom->find("#tr_250",0)->children(1)->plaintext;
		// 无钥匙进入系统
		if($dom->find("#tr_251",0)) $temp['wuyaoshijinruxitong'] = $dom->find("#tr_251",0)->find('p',0)->children(1)->plaintext;
		// 远程启动
		if($dom->find("#tr_252",0)) $temp['yuanchengqidong'] = $dom->find("#tr_252",0)->children(1)->plaintext;

		// 皮质方向盘
		if($dom->find("#tr_253",0)) $temp['pizhifangxiangpan'] = $dom->find("#tr_253",0)->children(1)->plaintext;
		// 方向盘电动
		if($dom->find("#tr_255",0)) $temp['fangxiangpandiandong'] = $dom->find("#tr_255",0)->children(1)->plaintext;
		// 多功能方向盘
		if($dom->find("#tr_256",0)) $temp['duogongnengfangxiangpan'] = $dom->find("#tr_256",0)->children(1)->plaintext;
		// 方向盘换挡
		if($dom->find("#tr_257",0)) $temp['fangxiangpanhuandang'] = $dom->find("#tr_257",0)->children(1)->plaintext;
		// 方向盘加热
		if($dom->find("#tr_258",0)) $temp['fangxiangpanjiare'] = $dom->find("#tr_258",0)->children(1)->plaintext;
		// 方向盘记忆
		if($dom->find("#tr_259",0)) $temp['fangxiangpanjiyi'] = $dom->find("#tr_259",0)->children(1)->plaintext;
		// 行车电脑显示屏
		if($dom->find("#tr_260",0)) $temp['xingchediannaoxianshiping'] = $dom->find("#tr_260",0)->children(1)->plaintext;
		// 全液晶仪表盘
		if($dom->find("#tr_261",0)) $temp['quanyejingyibiaopan'] = $dom->find("#tr_261",0)->children(1)->plaintext;
		// 互动抬头数字显示
		if($dom->find("#tr_262",0)) $temp['hudtaitoushuzixianshi'] = $dom->find("#tr_262",0)->children(1)->plaintext;
		// 内置行车记录仪
		if($dom->find("#tr_263",0)) $temp['neizhixingchejiluyi'] = $dom->find("#tr_263",0)->children(1)->plaintext;
		// 主动降噪
		if($dom->find("#tr_264",0)) $temp['zhudongjiangzao'] = $dom->find("#tr_264",0)->children(1)->plaintext;
		// 手机无线充电
		if($dom->find("#tr_265",0)) $temp['shoujiwuxianchongdian'] = $dom->find("#tr_265",0)->children(1)->plaintext;
						
		$empty = Capsule::table('car_configure')->where('car_id',$car_id)->get()->isEmpty();
		Capsule::table('car_configure')->insert($temp);


		// 座椅配置和多媒体配置
		$temp = array('car_id' => $car_id);
		// 座椅材质
		if($dom->find("#tr_266",0)) { }
		// 运动座椅风格
		if($dom->find("#tr_267",0)) $temp['yundongfenggezuoyi'] = $dom->find("#tr_267",0)->children(1)->find('p',0)->plaintext;
		// 座椅高低调节 
		if($dom->find("#tr_268",0)) $temp['zuoyigaoditiaojie'] = $dom->find("#tr_268",0)->children(1)->plaintext;
		// 腰部支撑调节
		if($dom->find("#tr_269",0)) $temp['yaobuzhichengtiaojie'] = $dom->find("#tr_269",0)->children(1)->find('p',0)->plaintext;
		// 肩部支撑调节
		if($dom->find("#tr_270",0)) $temp['jianbuzhichengtiaojie'] = $dom->find("#tr_270",0)->children(1)->plaintext;
		// 主副驾座电动调节
		if($dom->find("#tr_271",0)) $temp['zhufujiazuodiandongtiaojie'] = $dom->find("#tr_271",0)->children(1)->plaintext;
		// 第二排角度调节
		if($dom->find("#tr_272",0)) $temp['dierpaijiaodutiaojie'] = $dom->find("#tr_272",0)->children(1)->plaintext;
		// 第二排座椅移动
		if($dom->find("#tr_273",0)) $temp['dierpaizuoyiyidong'] = $dom->find("#tr_273",0)->children(1)->plaintext;
		// 后排座椅电动
		if($dom->find("#tr_274",0)) $temp['houpaizuoyidiandong'] = $dom->find("#tr_274",0)->children(1)->plaintext;
		// 副驾驶后排可调节
		if($dom->find("#tr_275",0)) $temp['fujiashihoupaiketiaojie'] = $dom->find("#tr_275",0)->children(1)->plaintext;
		// 电动座椅记忆
		if($dom->find("#tr_276",0)) $temp['diandongzuoyijiyi'] = $dom->find("#tr_276",0)->children(1)->plaintext;
		// 前后座椅加热
		if($dom->find("#tr_277",0)) $temp['qianhouzuoyijiare'] = $dom->find("#tr_277",0)->children(1)->plaintext;
		// 前后座椅通风
		if($dom->find("#tr_278",0)) $temp['qianhouzuoyitongfeng'] = $dom->find("#tr_278",0)->children(1)->plaintext;
		// 前后座椅按摩
		if($dom->find("#tr_279",0)) $temp['qianhouzuoyianmo'] = $dom->find("#tr_279",0)->children(1)->plaintext;
		// 第二排独立座椅
		if($dom->find("#tr_280",0)) $temp['dierpaidulizuoyi'] = $dom->find("#tr_280",0)->children(1)->plaintext;
		// 第三排座椅
		if($dom->find("#tr_281",0)) $temp['disanpaizuoyi'] = $dom->find("#tr_281",0)->children(1)->plaintext;
		// 后排座椅放倒方式
		if($dom->find("#tr_282",0)) { }
		// 前后中央扶手
		if($dom->find("#tr_283",0)) { }
		// 后排杯架
		if($dom->find("#tr_284",0)) $temp['houpaibeijia'] = $dom->find("#tr_284",0)->children(1)->plaintext;
		// 加热制冷杯架
		if($dom->find("#tr_285",0)) $temp['jiarezhilengbeijia'] = $dom->find("#tr_285",0)->children(1)->plaintext;
		// gps导航服务
		if($dom->find("#tr_286",0)) $temp['gpsdaohangfuwu'] = $dom->find("#tr_286",0)->children(1)->plaintext;
		// 定位互动
		if($dom->find("#tr_287",0)) $temp['dingweihudong'] = $dom->find("#tr_287",0)->children(1)->plaintext;
		// 中控台彩色大屏
		if($dom->find("#tr_288",0)) $temp['zhongkongtaicaisedaping'] = $dom->find("#tr_288",0)->children(1)->plaintext;
		// 中控台彩色大屏尺寸
		if($dom->find("#tr_289",0)) $temp['zhongkongtaicaisedapingchicun'] = $dom->find("#tr_289",0)->children(1)->plaintext;
		// 中控液晶屏分屏显示
		if($dom->find("#tr_290",0)) $temp['zhongkongyejingpingfenpingxianshi'] = $dom->find("#tr_290",0)->children(1)->plaintext;
		// 蓝牙车载电话
		if($dom->find("#tr_291",0)) $temp['lanyachezaidianhua'] = $dom->find("#tr_291",0)->children(1)->plaintext;
		// 手机互联映射
		if($dom->find("#tr_292",0)) $temp['shoujihulianyingshe'] = $dom->find("#tr_292",0)->children(1)->find('p',0)->plaintext;
		// 车联网
		if($dom->find("#tr_293",0)) $temp['chelianwang'] = $dom->find("#tr_293",0)->children(1)->plaintext;
		// 车载电视
		if($dom->find("#tr_294",0)) $temp['chezaidianshi'] = $dom->find("#tr_294",0)->children(1)->plaintext;
		// 后排液晶屏
		if($dom->find("#tr_295",0)) $temp['houpaiyejingping'] = $dom->find("#tr_295",0)->children(1)->plaintext;
		// 220v/230v电源
		if($dom->find("#tr_296",0)) $temp['dianyuan'] = $dom->find("#tr_296",0)->children(1)->plaintext;
		// 音源接口
		if($dom->find("#tr_297",0)) $temp['yinyuanjiekou'] = $dom->find("#tr_297",0)->children(1)->plaintext;
		// cddvd
		if($dom->find("#tr_298",0)) { }
		// 扬声器品牌
		if($dom->find("#tr_299",0)) $temp['yangshengqipinpai'] = $dom->find("#tr_299",0)->children(1)->plaintext;
		// 扬声器数量
		if($dom->find("#tr_300",0)) $temp['yangshengqishuliang'] = $dom->find("#tr_300",0)->children(1)->plaintext;

		$empty = Capsule::table('car_chair')->where('car_id',$car_id)->get()->isEmpty();
		Capsule::table('car_chair')->insert($temp);

		



		$temp = array('car_id' => $car_id);
		// 近光灯
		if($dom->find("#tr_301",0)) { }
		// 远光灯
		if($dom->find("#tr_302",0)) { }
		// 日间行车灯
		if($dom->find("#tr_303",0)) $temp['rijianxingchedeng'] = $dom->find("#tr_303",0)->children(1)->plaintext;
		// 自适应远近光
		if($dom->find("#tr_304",0)) $temp['zishiyingyuanjinguang'] = $dom->find("#tr_304",0)->children(1)->plaintext;
		// 自动头灯
		if($dom->find("#tr_305",0)) $temp['zidongtoudeng'] = $dom->find("#tr_305",0)->children(1)->plaintext;
		// 转向辅助灯
		if($dom->find("#tr_306",0)) $temp['zhuanxiangfuzhudeng'] = $dom->find("#tr_306",0)->children(1)->plaintext;
		// 转向头灯
		if($dom->find("#tr_307",0)) $temp['zhuanxiangtoudeng'] = $dom->find("#tr_307",0)->children(1)->plaintext;
		// 前雾灯
		if($dom->find("#tr_308",0)) $temp['qianwudeng'] = $dom->find("#tr_308",0)->children(1)->plaintext;
		// 大灯高度可调
		if($dom->find("#tr_309",0)) $temp['dadenggaoduketiao'] = $dom->find("#tr_309",0)->children(1)->plaintext;
		// 大灯清洗装置
		if($dom->find("#tr_310",0)) $temp['dadengqingxizhuangzhi'] = $dom->find("#tr_310",0)->children(1)->plaintext;
		// 车内氛围灯
		if($dom->find("#tr_311",0)) $temp['cheneifenweideng'] = $dom->find("#tr_311",0)->children(1)->plaintext;
		// 前后电动车窗
		if($dom->find("#tr_312",0)) { }
		// 车窗一键升降
		if($dom->find("#tr_313",0)) $temp['chechuangyijianshengjiang'] = $dom->find("#tr_313",0)->children(1)->plaintext;
		// 车窗防夹手功能
		if($dom->find("#tr_314",0)) $temp['chechuangfangjiashou'] = $dom->find("#tr_314",0)->children(1)->plaintext;
		// 防紫外线/隔热玻璃
		if($dom->find("#tr_315",0)) $temp['fangziwaixiangereboli'] = $dom->find("#tr_315",0)->children(1)->find('p',0)->plaintext;
		// 后视镜自动调节
		if($dom->find("#tr_316",0)) $temp['houshijingzidongtiaojie'] = $dom->find("#tr_316",0)->children(1)->plaintext;
		// 后视镜加热
		if($dom->find("#tr_317",0)) $temp['houshijingjiare'] = $dom->find("#tr_317",0)->children(1)->plaintext;
		// 后视镜自动防眩目
		if($dom->find("#tr_318",0)) $temp['houshijingzidongfangxuanmu'] = $dom->find("#tr_318",0)->children(1)->plaintext;
		// 流媒体车内后视镜
		if($dom->find("#tr_319",0)) $temp['liumeiticheneihoushijing'] = $dom->find("#tr_319",0)->children(1)->plaintext;
		// 后视镜电动折叠
		if($dom->find("#tr_320",0)) $temp['houshijingdiandongzhedie'] = $dom->find("#tr_320",0)->children(1)->plaintext;
		// 后视镜记忆
		if($dom->find("#tr_321",0)) $temp['houshijingjiyi'] = $dom->find("#tr_321",0)->children(1)->plaintext;
		// 后风挡遮阳帘
		if($dom->find("#tr_322",0)) $temp['houfengdangzheyanglian'] = $dom->find("#tr_322",0)->children(1)->plaintext;
		// 后排侧遮阳帘
		if($dom->find("#tr_323",0)) $temp['houpaicezheyanglian'] = $dom->find("#tr_323",0)->children(1)->plaintext;
		// 后排侧隐私玻璃
		if($dom->find("#tr_324",0)) $temp['houpaiceyinsiboli'] = $dom->find("#tr_324",0)->children(1)->plaintext;
		// 遮阳板化妆镜
		if($dom->find("#tr_325",0)) $temp['zheyangbanhuazhuangjing'] = $dom->find("#tr_325",0)->children(1)->plaintext;
		// 后雨刷
		if($dom->find("#tr_326",0)) $temp['houyushua'] = $dom->find("#tr_326",0)->children(1)->plaintext;
		// 感应雨刷
		if($dom->find("#tr_327",0)) $temp['ganyingyushua'] = $dom->find("#tr_327",0)->children(1)->plaintext;
		// 空调控制方式
		if($dom->find("#tr_328",0)) $temp['kongtiaokongzhifangshi'] = $dom->find("#tr_328",0)->children(1)->plaintext;
		// 后排独立空调
		if($dom->find("#tr_329",0)) $temp['houpaidulikongtiao'] = $dom->find("#tr_329",0)->children(1)->plaintext;
		// 后座出风口
		if($dom->find("#tr_330",0)) $temp['houzuochufengkou'] = $dom->find("#tr_330",0)->children(1)->plaintext;
		// 温度分区控制
		if($dom->find("#tr_331",0)) $temp['wendufenqukongzhi'] = $dom->find("#tr_331",0)->children(1)->plaintext;
		// 花粉过滤
		if($dom->find("#tr_332",0)) $temp['huafenguolv'] = $dom->find("#tr_332",0)->children(1)->plaintext;
		// 车载空气净化器
		if($dom->find("#tr_333",0)) $temp['chezaikongqijinghuaqi'] = $dom->find("#tr_333",0)->children(1)->plaintext;
		// 车载冰箱
		if($dom->find("#tr_334",0)) $temp['chezaibingxiang'] = $dom->find("#tr_334",0)->children(1)->plaintext;
		// 外观颜色
		if($dom->find("#tr_2003",0)) { }
		// 内饰颜色
		if($dom->find("#tr_2004",0)) { }

		$empty = Capsule::table('car_lighting')->where('car_id',$car_id)->get()->isEmpty();
		Capsule::table('car_lighting')->insert($temp);


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
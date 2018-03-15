<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;
use Illuminate\Database\Schema\Blueprint;
use GuzzleHttp\Client;

/**
  * 解析首页
  * @author xu
  * @copyright 2018/01/29
  */
class onestep{
	// 初始化所有数据表
	public static function initable()
	{
		// brand表
		if(!Capsule::schema()->hasTable('brand'))
		{
			Capsule::schema()->create('brand', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('brand')->nullable()->comment('品牌');
			});
			echo "table brand create".PHP_EOL;
		}

		// series
		if(!Capsule::schema()->hasTable('series'))
		{
			Capsule::schema()->create('series', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('brand')->nullable()->comment('品牌');
			    $table->string('subbrand')->nullable()->comment('子品牌');
			    $table->string('series')->nullable()->comment('车系');
			    $table->integer('series_num')->nullable()->comment('车型数量');
			});
			echo "table series create".PHP_EOL;
		}

		// model_list
		if(!Capsule::schema()->hasTable('model_list'))
		{
			Capsule::schema()->create('model_list', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('brand')->nullable()->comment('品牌');
			    $table->string('subbrand')->nullable()->comment('子品牌');
			    $table->string('series')->nullable()->comment('车系');
			});
			echo "table model_list create".PHP_EOL;
		}

		// model_detail
		if(!Capsule::schema()->hasTable('model_detail'))
		{
			Capsule::schema()->create('model_detail', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('brand')->nullable()->comment('品牌');
			    $table->string('subbrand')->nullable()->comment('子品牌');
			    $table->string('series')->nullable()->comment('车系');
			    $table->string('model')->nullable()->comment('车型');
			});
			echo "table model_detail create".PHP_EOL;
		}



		// car_basic
		if(!Capsule::schema()->hasTable('car_basic'))
		{
			Capsule::schema()->create('car_basic', function (Blueprint $table){
				$table->increments('id')->unique();
				$table->string('url')->nullable()->comment('url');
				$table->string('md5_url')->unique()->comment('md5_url');

				$table->string('brand')->nullable()->comment('品牌');
				$table->string('subbrand')->nullable()->comment('子品牌');
				$table->string('series')->nullable()->comment('车系');
				$table->string('model')->nullable()->comment('车型');

				$table->string('changshang')->nullable()->comment('厂商');
				$table->string('jibei')->nullable()->comment('级别');
				$table->string('nengyuanleixing')->nullable()->comment('能源类型');
				$table->string('shangshishijian')->nullable()->comment('上市时间');
				$table->string('zuidagonglv')->nullable()->comment('最大功率（kw）');
				$table->string('zuidaniuju')->nullable()->comment('最大扭矩（N·m）');
				$table->string('faodngji')->nullable()->comment('发动机');
				$table->string('biansuxiang')->nullable()->comment('变速箱');
				$table->string('changkuangao')->nullable()->comment('长*宽*高');
				$table->string('cheshenjiegou')->nullable()->comment('车身结构');
				$table->string('zuigaochesu')->nullable()->comment('最高车速');
				$table->string('guanfangjiasu')->nullable()->comment('官方0-100km/h加速(s)');
				$table->string('shicejiasu')->nullable()->comment('实测0-100km/s加速');
				$table->string('shicezhidong')->nullable()->comment('实测100km/s-o制动(m)');
				$table->string('shicelidijianxi')->nullable()->comment('实测离地间隙（mm）');
				$table->string('gongxinbuyouhao')->nullable()->comment('工信部综合油耗（L/100km）');
				$table->string('shiceyouhao')->nullable()->comment('实测油耗(L/100km)');
				$table->string('zhengchezhibao')->nullable()->comment('整车质保');

			});
			echo "table car_basic create".PHP_EOL;
		}

		// car_body
		if(!Capsule::schema()->hasTable('car_body'))
		{
			Capsule::schema()->create('car_body', function (Blueprint $table){

				$table->integer('car_id')->unique();

				$table->string('cheshenchangdu')->nullable()->comment('车身长度');
				$table->string('cheshenkuandu')->nullable()->comment('车身宽度');
				$table->string('cheshengaodu')->nullable()->comment('车身高度');
				$table->string('cheshenzhouju')->nullable()->comment('车身轴距（mm）');
				$table->string('cheshenqianlunju')->nullable()->comment('车身前轮距（mm）');
				$table->string('cheshenhoulunju')->nullable()->comment('车身后轮距（mm）');
				$table->string('cheshenzuixiaolidijianxi')->nullable()->comment('车身最小离地间隙（mm）');
				$table->string('cheshencheshenjiegou')->nullable()->comment('车身结构');
				$table->string('cheshenchemenshu')->nullable()->comment('车身车门数（个）');
				$table->string('cheshenzuoweishu')->nullable()->comment('车身座位数（个）');
				$table->string('cheshenyouxiangrongji')->nullable()->comment('车身油箱容积(L)');
				$table->string('xinglixiangrongji')->nullable()->comment('车身行李箱容积(L)');
				$table->string('zhengbeizhiliang')->nullable()->comment('车身整备质量(kg)');

			});
			echo "table car_body create".PHP_EOL;
		}


		// car_engine
		if(!Capsule::schema()->hasTable('car_engine'))
		{
			Capsule::schema()->create('car_engine', function (Blueprint $table){

				$table->integer('car_id')->unique();

				$table->string('fadongjixinghao')->nullable()->comment('发动机型号');
				$table->string('pailiangml')->nullable()->comment('发动机排量（ml）');
				$table->string('pailiangl')->nullable()->comment('发动机排量（l）');
				$table->string('jingqixingshi')->nullable()->comment('发动机进气形式');
				$table->string('qigangpailiexingshi')->nullable()->comment('气缸排列形式');
				$table->string('qigangshu')->nullable()->comment('发动机气缸数（个）');
				$table->string('meigangqimenshu')->nullable()->comment('发动机每钢气门数（个）');
				$table->string('yasuobi')->nullable()->comment('发动机压缩比');
				$table->string('peiqijiegou')->nullable()->comment('发动机配气结构');
				$table->string('gangjing')->nullable()->comment('发动机缸径（mm）');
				$table->string('xingcheng')->nullable()->comment('发动机行程（mm）');
				$table->string('zuidamali')->nullable()->comment('发动机最大马力（ps）(L)');
				$table->string('fadongjizuidagonglv')->nullable()->comment('发动机最大功率（kw）(L)');
				$table->string('zuidagonglvzhuansu')->nullable()->comment('发动机最大功率转速（rpm）(kg)');
				$table->string('fadongjizuidaniuju')->nullable()->comment('发动机最大扭矩（N');
				$table->string('zuidaniujuzhuansu')->nullable()->comment('发动机最大扭矩转速（rpm）');
				$table->string('teyoujishu')->nullable()->comment('发动机特有技术');
				$table->string('ranliaoxingshi')->nullable()->comment('发动机燃料形式');
				$table->string('ranyoubianhao')->nullable()->comment('发动机燃油标号(L)');
				$table->string('gongyoufangshi')->nullable()->comment('发动机供油方式(L)');
				$table->string('ganggaicailiao')->nullable()->comment('发动机缸盖材料(kg)');
				$table->string('gangticailiao')->nullable()->comment('发动机缸体材料(L)');
				$table->string('huanbaobiaozhun')->nullable()->comment('发动机环保标准(kg)');
			});
			echo "table car_engine create".PHP_EOL;
		}


		// 制动箱 and 底盘转向 车轮制动 and 主被动安全装 
		if(!Capsule::schema()->hasTable('car_gearbox'))
		{
			Capsule::schema()->create('car_gearbox', function (Blueprint $table){

				$table->integer('car_id')->unique();

				$table->string('dangweigeshu')->nullable()->comment('变速箱档位个数');
				$table->string('biansuxiangleixing')->nullable()->comment('变速箱类型');
				$table->string('biansuxiangjiancheng')->nullable()->comment('变速箱简称');

				$table->string('dipanqudongfangshi')->nullable()->comment('底盘转向驱动方式');
				$table->string('qianxuangualeixing')->nullable()->comment('底盘转向前悬架类型');
				$table->string('houxuangualeixing')->nullable()->comment('底盘转向后悬架类型');
				$table->string('zhulileixing')->nullable()->comment('底盘转向助力类型');
				$table->string('chetijiegou')->nullable()->comment('底盘转向车体结构');

				$table->string('qianzhidongqileixing')->nullable()->comment('车轮制动-前制动器类型');
				$table->string('houzhidongqileixing')->nullable()->comment('车轮制动-后制动器类型');
				$table->string('zhuchezhidongleixing')->nullable()->comment('车轮制动-驻车制动类型');
				$table->string('qianluntaiguige')->nullable()->comment('车轮制动-前轮胎规格');
				$table->string('houluntaiguige')->nullable()->comment('车轮制动-后轮胎规格');
				$table->string('beitaiguige')->nullable()->comment('车轮制动-备胎规格');


				$table->string('zhufujiazuoanquanqinang')->nullable()->comment('主/被动安全设备-主/副驾驶座安全气囊');
				$table->string('qianhoucepaiqinang')->nullable()->comment('主/被动安全设备-前后排侧气囊');
				$table->string('qianhoupaitoubuqinang')->nullable()->comment('主/被动安全设备-前后排头部气囊(气帘)');
				$table->string('xibuqinang')->nullable()->comment('主/被动安全设备-膝部气囊');
				$table->string('taiyajiancezhuangzhi')->nullable()->comment('主/被动安全设备-胎压监测装置');
				$table->string('lingtaiyajixuxingshi')->nullable()->comment('主/被动安全设备-零胎压继续行驶');
				$table->string('anquandaiweixitishi')->nullable()->comment('主/被动安全设备-安全带未系提示');
				$table->string('ertongzuoyijiekou')->nullable()->comment('主/被动安全设备-ISOFIX儿童座椅接口');
				$table->string('absfangbaosi')->nullable()->comment('主/被动安全设备-ABS防抱死');
				$table->string('zhidonglifenpei')->nullable()->comment('主/被动安全设备-制动力分配（EBD/BS等）');
				$table->string('shachefuzhu')->nullable()->comment('主/被动安全设备-刹车辅助(EBA/BAS/BA等)');
				$table->string('qianyinlikongzhi')->nullable()->comment('主/被动安全设备-牵引力控制（ASR/TCS/TRC等）');
				$table->string('cheshenwendingkongzhi')->nullable()->comment('主/被动安全设备-车身稳定控制（ESC/ESP/DSC等）');
				$table->string('bingxianfuzhu')->nullable()->comment('主/被动安全设备-并线辅助');
				$table->string('chedaopianliyujing')->nullable()->comment('主/被动安全设备-车道偏离预警系统');
				$table->string('zhubeidonganquanxitong')->nullable()->comment('主/被动安全设备-主动刹车/主动安全系统');
				$table->string('yeshixitong')->nullable()->comment('主/被动安全设备-夜视系统');
				$table->string('pilaojiashi')->nullable()->comment('主/被动安全设备-疲劳驾驶提示');

			});
			echo "table car_gearbox create".PHP_EOL;
		}

		// 操控配置和防盗配置
		if(!Capsule::schema()->hasTable('car_configure'))
		{
			Capsule::schema()->create('car_configure', function (Blueprint $table){

				$table->integer('car_id')->unique();

				$table->string('qianhouzhucheleida')->nullable()->comment('辅助/操控配置-前/后驻车雷达');
				$table->string('daocheshipingyingxiang')->nullable()->comment('辅助/操控配置-倒车视频影像');
				$table->string('quanjingshexiangtou')->nullable()->comment('辅助/操控配置-全景摄像头');
				$table->string('dingsuxunhang')->nullable()->comment('辅助/操控配置-定速巡航');
				$table->string('zishiyingxunhang')->nullable()->comment('辅助/操控配置-自适应巡航');
				$table->string('zidongboche')->nullable()->comment('辅助/操控配置-自动泊车入围');
				$table->string('fadongjiqiting')->nullable()->comment('辅助/操控配置-发动机启停技术');
				$table->string('zidongjiashi')->nullable()->comment('辅助/操控配置-自动驾驶技术');
				$table->string('shangpofuzhu')->nullable()->comment('辅助/操控配置-上坡辅助');
				$table->string('zidongzhuche')->nullable()->comment('辅助/操控配置-自动驻车');
				$table->string('doupohuanjiang')->nullable()->comment('辅助/操控配置-陡坡缓降');
				$table->string('kebianxuangua')->nullable()->comment('辅助/操控配置-可变悬挂');
				$table->string('kongqixuangua')->nullable()->comment('辅助/操控配置-空气悬挂');
				$table->string('dianciganyingxuangua')->nullable()->comment('辅助/操控配置-电磁感应悬挂');
				$table->string('kebianzhuanxianghua')->nullable()->comment('辅助/操控配置-可变转向化');
				$table->string('qianqiaochasuqi')->nullable()->comment('辅助/操控配置-前桥限滑差速器/差速锁');
				$table->string('zhongyangchasuqi')->nullable()->comment('辅助/操控配置-中央差速器锁止功能');
				$table->string('houqiaochasuqi')->nullable()->comment('辅助/操控配置-后桥限滑差速器/差速锁');
				$table->string('zhengchezhudongzhuanxiang')->nullable()->comment('辅助/操控配置-整车主动转向系统');

				$table->string('diandongtianchuang')->nullable()->comment('外部/防盗配置-电动天窗');
				$table->string('quanjingtianchuang')->nullable()->comment('外部/防盗配置-全景天窗');
				$table->string('duotianchuang')->nullable()->comment('外部/防盗配置-多天窗');
				$table->string('yundongwaiguantaojian')->nullable()->comment('外部/防盗配置-运动外观套件');
				$table->string('lvhejinlunquan')->nullable()->comment('外部/防盗配置-铝合金轮圈');
				$table->string('diandongxihemen')->nullable()->comment('外部/防盗配置-电动吸合门');
				$table->string('cehuamen')->nullable()->comment('外部/防盗配置-侧滑门');
				$table->string('diandonghoubeixiang')->nullable()->comment('外部/防盗配置-电动后备箱');
				$table->string('ganyinghoubeixiang')->nullable()->comment('外部/防盗配置-感应后备箱');
				$table->string('chedingxinglijia')->nullable()->comment('外部/防盗配置-车顶行李架');
				$table->string('fadongjidianzifangdao')->nullable()->comment('外部/防盗配置-发动机电子防盗');
				$table->string('cheneizhongkongsuo')->nullable()->comment('外部/防盗配置-车内中控锁');
				$table->string('yaokongyaoshi')->nullable()->comment('外部/防盗配置-遥控钥匙');
				$table->string('wuyaoshiqidongxitong')->nullable()->comment('外部/防盗配置-无钥匙启动系统');
				$table->string('wuyaoshijinruxitong')->nullable()->comment('外部/防盗配置-无钥匙进入系统');
				$table->string('yuanchengqidong')->nullable()->comment('外部/防盗配置-远程启动');
				
				$table->string('pizhifangxiangpan')->nullable()->comment('内部配置-皮质方向盘');
				$table->string('fangxiangpantiaojie')->nullable()->comment('内部配置-方向盘调节');
				$table->string('fangxiangpandiandong')->nullable()->comment('内部配置-方向盘电动调节');
				$table->string('duogongnengfangxiangpan')->nullable()->comment('内部配置-多功能方向盘');
				$table->string('fangxiangpanhuandang')->nullable()->comment('内部配置-方向盘换挡');
				$table->string('fangxiangpanjiare')->nullable()->comment('内部配置-方向盘加热');
				$table->string('fangxiangpanjiyi')->nullable()->comment('内部配置-方向盘记忆');
				$table->string('xingchediannaoxianshiping')->nullable()->comment('内部配置-行车电脑显示屏');
				$table->string('quanyejingyibiaopan')->nullable()->comment('内部配置-全液晶仪表盘');
				$table->string('hudtaitoushuzixianshi')->nullable()->comment('内部配置-HUD抬头数字显示');
				$table->string('neizhixingchejiluyi')->nullable()->comment('内部配置-内置行车记录仪');
				$table->string('zhudongjiangzao')->nullable()->comment('内部配置-主动降噪');
				$table->string('shoujiwuxianchongdian')->nullable()->comment('内部配置-手机无线充电');

			});
			echo "table car_configure create".PHP_EOL;
		}

		// 座椅配置和多媒体配置
		if(!Capsule::schema()->hasTable('car_chair'))
		{
			Capsule::schema()->create('car_chair', function (Blueprint $table){
				$table->integer('car_id')->unique();

				$table->string('zuoyicaizhi')->nullable()->comment('座椅配置-座椅材质');
				$table->string('yundongfenggezuoyi')->nullable()->comment('座椅配置-运动风格座椅');
				$table->string('zuoyigaoditiaojie')->nullable()->comment('座椅配置-座椅高低调节');
				$table->string('yaobuzhichengtiaojie')->nullable()->comment('座椅配置-腰部支撑调节');
				$table->string('jianbuzhichengtiaojie')->nullable()->comment('座椅配置-肩部支撑调节');
				$table->string('zhufujiazuodiandongtiaojie')->nullable()->comment('座椅配置-主/副驾驶座电动调节');
				$table->string('dierpaijiaodutiaojie')->nullable()->comment('座椅配置-第二排靠背角度调节');
				$table->string('dierpaizuoyiyidong')->nullable()->comment('座椅配置-第二排座椅移动');
				$table->string('houpaizuoyidiandong')->nullable()->comment('座椅配置-后排座椅电动调节');
				$table->string('fujiashihoupaiketiaojie')->nullable()->comment('座椅配置-副驾驶位后排可调节按钮');
				$table->string('diandongzuoyijiyi')->nullable()->comment('座椅配置-电动座椅记忆');
				$table->string('qianhouzuoyijiare')->nullable()->comment('座椅配置-前/后排座椅加热');
				$table->string('qianhouzuoyitongfeng')->nullable()->comment('座椅配置-前/后排座椅通风');
				$table->string('qianhouzuoyianmo')->nullable()->comment('座椅配置-前/后排座椅按摩');
				$table->string('dierpaidulizuoyi')->nullable()->comment('座椅配置-第二排独立座椅');
				$table->string('disanpaizuoyi')->nullable()->comment('座椅配置-第三排座椅');
				$table->string('houpaizuoyifangdaofangshi')->nullable()->comment('座椅配置-后排座椅放倒方式');
				$table->string('qianhouzhongyangfushou')->nullable()->comment('座椅配置-前/后中央扶手');
				$table->string('houpaibeijia')->nullable()->comment('座椅配置-后排杯架');
				$table->string('jiarezhilengbeijia')->nullable()->comment('座椅配置-可加热/制冷杯架');

				$table->string('gpsdaohangfuwu')->nullable()->comment('多媒体配置-GPS导航服务');
				$table->string('dingweihudong')->nullable()->comment('多媒体配置-定位互动服务');
				$table->string('zhongkongtaicaisedaping')->nullable()->comment('多媒体配置-中控台彩色大屏');
				$table->string('zhongkongtaicaisedapingchicun')->nullable()->comment('多媒体配置-中控台彩色大屏尺寸');
				$table->string('zhongkongyejingpingfenpingxianshi')->nullable()->comment('多媒体配置-中控液晶屏分屏显示');
				$table->string('lanyachezaidianhua')->nullable()->comment('多媒体配置-蓝牙车载电话');
				$table->string('shoujihulianyingshe')->nullable()->comment('多媒体配置-手机互联/映射');
				$table->string('chelianwang')->nullable()->comment('多媒体配置-车联网');
				$table->string('chezaidianshi')->nullable()->comment('多媒体配置-车载电视');
				$table->string('houpaiyejingping')->nullable()->comment('多媒体配置-后排液晶屏');
				$table->string('dianyuan')->nullable()->comment('多媒体配置-220V/230V电源');
				$table->string('yinyuanjiekou')->nullable()->comment('多媒体配置-外接音源接口');
				$table->string('cddvd')->nullable()->comment('多媒体配置-CD/DVD');
				$table->string('yangshengqipinpai')->nullable()->comment('多媒体配置-扬声器品牌');
				$table->string('yangshengqishuliang')->nullable()->comment('多媒体配置-扬声器数量');

			});
			echo "table car_chair create".PHP_EOL;
		}

		// 灯光配置和后视镜配置\空调冰箱配置
		if(!Capsule::schema()->hasTable('car_lighting'))
		{
			Capsule::schema()->create('car_lighting', function (Blueprint $table){
				$table->integer('car_id')->unique();

				$table->string('jinguangdeng')->nullable()->comment('灯光配置-近光灯');
				$table->string('yuanguangdeng')->nullable()->comment('灯光配置-远光灯');
				$table->string('rijianxingchedeng')->nullable()->comment('灯光配置-LED日间行车灯');
				$table->string('zishiyingyuanjinguang')->nullable()->comment('灯光配置-自适应远近光');
				$table->string('zidongtoudeng')->nullable()->comment('灯光配置-自动头灯');
				$table->string('zhuanxiangfuzhudeng')->nullable()->comment('灯光配置-转向辅助灯');
				$table->string('zhuanxiangtoudeng')->nullable()->comment('灯光配置-转向头灯');
				$table->string('qianwudeng')->nullable()->comment('灯光配置-前雾灯');
				$table->string('dadenggaoduketiao')->nullable()->comment('灯光配置-大灯高度可调');
				$table->string('dadengqingxizhuangzhi')->nullable()->comment('灯光配置-大灯清洗装置');
				$table->string('cheneifenweideng')->nullable()->comment('灯光配置-车内氛围灯');

				$table->string('qianhoudiandongchechuang')->nullable()->comment('玻璃/后视镜-前/后电动车窗');
				$table->string('chechuangyijianshengjiang')->nullable()->comment('玻璃/后视镜-车窗一键升降');
				$table->string('chechuangfangjiashou')->nullable()->comment('玻璃/后视镜-车窗防夹手功能');
				$table->string('fangziwaixiangereboli')->nullable()->comment('玻璃/后视镜-防紫外线/隔热玻璃');
				$table->string('houshijingdiandongtiaojie')->nullable()->comment('玻璃/后视镜-后视镜电动调节');
				$table->string('houshijingjiare')->nullable()->comment('玻璃/后视镜-后视镜加热');
				$table->string('houshijingzidongfangxuanmu')->nullable()->comment('玻璃/后视镜-内/外后视镜自动防眩目');
				$table->string('liumeiticheneihoushijing')->nullable()->comment('玻璃/后视镜-流媒体车内后视镜');
				$table->string('houshijingdiandongzhedie')->nullable()->comment('玻璃/后视镜-后视镜电动折叠');
				$table->string('houshijingjiyi')->nullable()->comment('玻璃/后视镜-后视镜记忆');
				$table->string('houfengdangzheyanglian')->nullable()->comment('玻璃/后视镜-后风挡遮阳帘');
				$table->string('houpaicezheyanglian')->nullable()->comment('玻璃/后视镜-后排侧遮阳帘');
				$table->string('houpaiceyinsiboli')->nullable()->comment('玻璃/后视镜-后排侧隐私玻璃');
				$table->string('zheyangbanhuazhuangjing')->nullable()->comment('玻璃/后视镜-遮阳板化妆镜');
				$table->string('houyushua')->nullable()->comment('玻璃/后视镜-后雨刷');
				$table->string('ganyingyushua')->nullable()->comment('玻璃/后视镜-感应雨刷');

				$table->string('kongtiaokongzhifangshi')->nullable()->comment('空调/冰箱-空调控制方式');
				$table->string('houpaidulikongtiao')->nullable()->comment('空调/冰箱-后排独立空调');
				$table->string('houzuochufengkou')->nullable()->comment('空调/冰箱-后座出风口');
				$table->string('wendufenqukongzhi')->nullable()->comment('空调/冰箱-温度分区控制');
				$table->string('huafenguolv')->nullable()->comment('空调/冰箱-车内空气调节/花粉过滤');
				$table->string('chezaikongqijinghuaqi')->nullable()->comment('空调/冰箱-车载空气净化器');
				$table->string('chezaibingxiang')->nullable()->comment('空调/冰箱-车载冰箱');
				$table->string('waiguanyanse')->nullable()->comment('空调/冰箱-外观颜色');
				$table->string('neishiyanse')->nullable()->comment('空调/冰箱-内饰颜色');

			});
			echo "table car_lighting create".PHP_EOL;
		}

	}
	// 获取品牌链接
	public static function brand()
	{
		$prefix = 'https://car.autohome.com.cn';
		// 解析首页
		$client = new Client();
		
		$config = [
			'verify' => false,
			// 'proxy'=> "http://127.0.0.1:9668"
		];
		$response = $client->get('https://car.autohome.com.cn/AsLeftMenu/As_LeftListNew.ashx?typeId=1%20&brandId=0%20&fctId=0%20&seriesId=0',$config);
		
		@mkdir(PROJECT_APP_DOWN, 0777, true);
		// 保存首页
		file_put_contents(PROJECT_APP_DOWN.'index.html', $response->getBody());

		$html = file_get_contents(PROJECT_APP_DOWN.'index.html');

		$html = ltrim($html,'document.writeln("');
		$html = rtrim($html,'");');

		// 字符编码转换
		$html = mb_convert_encoding($html,"UTF-8", "gb2312");
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html($html))
		{
			foreach($dom->find('li a') as $a)
			{
				$brandId =rtrim(ltrim($a->href,'/price/brand-'),'.html');

				$url = $prefix.'/AsLeftMenu/As_LeftListNew.ashx?typeId=1&brandId='.$brandId.'&fctId=0&seriesId=0';
			    // 存储
			    $temp = [
			    	'url' => $url,
			    	'status' => 'wait',
			    	'md5_url' => md5($url),
			    	'brand' => preg_replace('/\(\d+\)/','',$a->plaintext)
			    ];

			    $empty = Capsule::table('brand')
			    	->where('md5_url',md5($url))
			    	->get()
			    	->isEmpty();
			    if($empty)
			    {
				    Capsule::table('brand')->insert($temp);					    	
			    }
			}
			echo 'brand analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}
}
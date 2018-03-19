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
  * 转移数据
  * @author xu
  * @copyright 2018/03/16
  */
class sixstep{

	// 建表
	public static function get()
	{	

		$empty = Capsule::table('raw_data')->where('status','wait')->get()->isEmpty();

		while (!$empty) {

			$datas = Capsule::table('raw_data')->where('status','wait')->limit(800)->get();
			// json文件是否存在
			if(!file_exists(PROJECT_APP_DOWN.'every_column.json')) 
				file_put_contents(PROJECT_APP_DOWN.'every_column.json', json_encode([['my_test','1']]));

			$every_column = json_decode(file_get_contents(PROJECT_APP_DOWN.'every_column.json'),true);

			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 当前所有车参数
		    	$json = json_decode($data->data,true);

		    	foreach ($json as $name => $v)
		    	{	
		    		// 数量
		    		$num = mb_strlen($v);

		    		// 名称存在且数量大于则删除原来的并加上大的
		    		foreach ($every_column as $kk => $vv) {
		    			// 如果存在且数量大于
		    			if(($name == $vv[0]) && ($num > $vv[1]) )
		    			{
		    				unset($every_column[$kk]);
		    				$every_column[] = [$name,$num];
		    			}
		    		}
		    		// 如果不存在与列之中
		    		$all_name = array_column($every_column,'0');
		    		

		    		if(!in_array($name,$all_name))
		    		{
		    			$every_column[] = [$name,$num];
		    		}

		    	}
		    	file_put_contents(PROJECT_APP_DOWN.'every_column.json', json_encode($every_column));
	            // 更改SQL语句
	            Capsule::table('raw_data')->where('id', $data->id)->update(['status' =>'readed']);
			    // 命令行执行时候不需要经过apache直接输出在窗口
	            echo 'raw_data '.$data->id.'.html'."  analyse successful!".PHP_EOL;
		    }

		    $empty = Capsule::table('raw_data')->where('status','wait')->get()->isEmpty();
			
		}

		// 根据获取的列和数量进行-建表
	    $every_column = json_decode(file_get_contents(PROJECT_APP_DOWN.'every_column.json'),true);	

	    $pinyin = new Pinyin(); 
	    // 建表语句
	    $create_table_str = '';
	    // 文字对拼音
	    $text_to_pinyin = array();

	    foreach ($every_column as $k => $v)
	    {
	    	$this_column = $pinyin->abbr($v[0]);

	    	$text_to_pinyin[$v[0]] = $this_column;
	    	
	    	$create_table_str .= '$table->char("'.$this_column.'", '.$v[1].')->nullable()->comment("'.$v[0].'");';
	    }
	   	// 将文字对拼音转存到json
	   	file_put_contents(PROJECT_APP_DOWN.'text_to_pinyin.json', json_encode($text_to_pinyin)); 

	   	// 输出建表语句
	    echo $create_table_str;

	}

	// 建表
	public static function initable()
	{
		// car
		if(!Capsule::schema()->hasTable('car'))
		{
			Capsule::schema()->create('car', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('brand')->nullable()->comment('品牌');
			    $table->string('subbrand')->nullable()->comment('子品牌');
			    $table->string('series')->nullable()->comment('车系');
			    $table->string('model')->nullable()->comment('车型');
			 	// 在这里接上上面获取的所有字段以及最大数量的建表语句
				$table->char("cs", 12)->nullable()->comment("厂商");
				$table->char("sssj", 7)->nullable()->comment("上市时间");
				$table->char("zkgm", 14)->nullable()->comment("长*宽*高(mm)");
				$table->char("zgcskh", 3)->nullable()->comment("最高车速(km/h)");
				$table->char("cdm", 4)->nullable()->comment("长度(mm)");
				$table->char("kdm", 4)->nullable()->comment("宽度(mm)");
				$table->char("gdm", 4)->nullable()->comment("高度(mm)");
				$table->char("zjm", 4)->nullable()->comment("轴距(mm)");
				$table->char("qljm", 4)->nullable()->comment("前轮距(mm)");
				$table->char("hljm", 4)->nullable()->comment("后轮距(mm)");
				$table->char("zxldjxm", 3)->nullable()->comment("最小离地间隙(mm)");
				$table->char("cmsg", 1)->nullable()->comment("车门数(个)");
				$table->char("zbzlk", 4)->nullable()->comment("整备质量(kg)");
				$table->char("qgplxs", 1)->nullable()->comment("气缸排列形式");
				$table->char("pqjg", 4)->nullable()->comment("配气机构");
				$table->char("zdglzsr", 9)->nullable()->comment("最大功率转速(rpm)");
				$table->char("rybh", 3)->nullable()->comment("燃油标号");
				$table->char("qzdqlx", 6)->nullable()->comment("前制动器类型");
				$table->char("hzdqlx", 6)->nullable()->comment("后制动器类型");
				$table->char("zfjszaqqn", 17)->nullable()->comment("主/副驾驶座安全气囊");
				$table->char("qhpcqn", 17)->nullable()->comment("前/后排侧气囊");
				$table->char("qhptbqnql", 17)->nullable()->comment("前/后排头部气囊(气帘)");
				$table->char("xbqn", 1)->nullable()->comment("膝部气囊");
				$table->char("tyjczz", 1)->nullable()->comment("胎压监测装置");
				$table->char("ltyjxxs", 1)->nullable()->comment("零胎压继续行驶");
				$table->char("aqdwxts", 1)->nullable()->comment("安全带未系提示");
				$table->char("Ietzyjk", 1)->nullable()->comment("ISOFIX儿童座椅接口");
				$table->char("Afbs", 1)->nullable()->comment("ABS防抱死");
				$table->char("zdlfpECd", 1)->nullable()->comment("制动力分配(EBD/CBC等)");
				$table->char("scfzEBBd", 1)->nullable()->comment("刹车辅助(EBA/BAS/BA等)");
				$table->char("qylkzATTd", 1)->nullable()->comment("牵引力控制(ASR/TCS/TRC等)");
				$table->char("cswdkzEEDd", 1)->nullable()->comment("车身稳定控制(ESC/ESP/DSC等)");
				$table->char("bxfz", 1)->nullable()->comment("并线辅助");
				$table->char("cdplyjxt", 1)->nullable()->comment("车道偏离预警系统");
				$table->char("zdsczdaqxt", 1)->nullable()->comment("主动刹车/主动安全系统");
				$table->char("ysxt", 1)->nullable()->comment("夜视系统");
				$table->char("qhzcld", 17)->nullable()->comment("前/后驻车雷达");
				$table->char("dcspyx", 1)->nullable()->comment("倒车视频影像");
				$table->char("qjsxt", 1)->nullable()->comment("全景摄像头");
				$table->char("dsxh", 1)->nullable()->comment("定速巡航");
				$table->char("zsyxh", 1)->nullable()->comment("自适应巡航");
				$table->char("zdbcrw", 1)->nullable()->comment("自动泊车入位");
				$table->char("fdjqtjs", 1)->nullable()->comment("发动机启停技术");
				$table->char("spfz", 1)->nullable()->comment("上坡辅助");
				$table->char("zdzc", 1)->nullable()->comment("自动驻车");
				$table->char("dphj", 1)->nullable()->comment("陡坡缓降");
				$table->char("kqxj", 1)->nullable()->comment("空气悬架");
				$table->char("kbzxb", 1)->nullable()->comment("可变转向比");
				$table->char("zycsqszgn", 1)->nullable()->comment("中央差速器锁止功能");
				$table->char("ztzdzxxt", 1)->nullable()->comment("整体主动转向系统");
				$table->char("ddtc", 1)->nullable()->comment("电动天窗");
				$table->char("qjtc", 1)->nullable()->comment("全景天窗");
				$table->char("ydwgtj", 1)->nullable()->comment("运动外观套件");
				$table->char("lhjlq", 1)->nullable()->comment("铝合金轮圈");
				$table->char("ddxhm", 1)->nullable()->comment("电动吸合门");
				$table->char("ddhbx", 1)->nullable()->comment("电动后备厢");
				$table->char("gyhbx", 1)->nullable()->comment("感应后备厢");
				$table->char("cdxlj", 1)->nullable()->comment("车顶行李架");
				$table->char("fdjdzfd", 1)->nullable()->comment("发动机电子防盗");
				$table->char("cnzks", 1)->nullable()->comment("车内中控锁");
				$table->char("ykys", 1)->nullable()->comment("遥控钥匙");
				$table->char("wysqdxt", 1)->nullable()->comment("无钥匙启动系统");
				$table->char("wysjrxt", 1)->nullable()->comment("无钥匙进入系统");
				$table->char("pzfxp", 1)->nullable()->comment("皮质方向盘");
				$table->char("fxpddtj", 1)->nullable()->comment("方向盘电动调节");
				$table->char("dgnfxp", 1)->nullable()->comment("多功能方向盘");
				$table->char("fxphd", 1)->nullable()->comment("方向盘换挡");
				$table->char("fxpjr", 1)->nullable()->comment("方向盘加热");
				$table->char("fxpjy", 1)->nullable()->comment("方向盘记忆");
				$table->char("xcdnxsp", 1)->nullable()->comment("行车电脑显示屏");
				$table->char("qyjybp", 1)->nullable()->comment("全液晶仪表盘");
				$table->char("Httszxs", 1)->nullable()->comment("HUD抬头数字显示");
				$table->char("ydfgzy", 1)->nullable()->comment("运动风格座椅");
				$table->char("zygdtj", 1)->nullable()->comment("座椅高低调节");
				$table->char("ybzctj", 1)->nullable()->comment("腰部支撑调节");
				$table->char("jbzctj", 1)->nullable()->comment("肩部支撑调节");
				$table->char("zfjszddtj", 17)->nullable()->comment("主/副驾驶座电动调节");
				$table->char("depkbjdtj", 1)->nullable()->comment("第二排靠背角度调节");
				$table->char("depzyyd", 1)->nullable()->comment("第二排座椅移动");
				$table->char("hpzyddtj", 1)->nullable()->comment("后排座椅电动调节");
				$table->char("ddzyjy", 1)->nullable()->comment("电动座椅记忆");
				$table->char("qhzyfs", 17)->nullable()->comment("前/后中央扶手");
				$table->char("hpbj", 1)->nullable()->comment("后排杯架");
				$table->char("Gdhxt", 1)->nullable()->comment("GPS导航系统");
				$table->char("dwhdfw", 1)->nullable()->comment("定位互动服务");
				$table->char("zktcsdp", 1)->nullable()->comment("中控台彩色大屏");
				$table->char("zkyjpfpxs", 1)->nullable()->comment("中控液晶屏分屏显示");
				$table->char("lyczdh", 1)->nullable()->comment("蓝牙/车载电话");
				$table->char("czds", 1)->nullable()->comment("车载电视");
				$table->char("hpyjp", 1)->nullable()->comment("后排液晶屏");
				$table->char("22dy", 1)->nullable()->comment("220V/230V电源");
				$table->char("Lrjxcd", 1)->nullable()->comment("LED日间行车灯");
				$table->char("zsyyjg", 1)->nullable()->comment("自适应远近光");
				$table->char("zdtd", 1)->nullable()->comment("自动头灯");
				$table->char("zxfzd", 1)->nullable()->comment("转向辅助灯");
				$table->char("zxtd", 1)->nullable()->comment("转向头灯");
				$table->char("qwd", 1)->nullable()->comment("前雾灯");
				$table->char("ddgdkt", 1)->nullable()->comment("大灯高度可调");
				$table->char("ddqxzz", 1)->nullable()->comment("大灯清洗装置");
				$table->char("cnfwd", 1)->nullable()->comment("车内氛围灯");
				$table->char("qhddcc", 17)->nullable()->comment("前/后电动车窗");
				$table->char("ccfjsgn", 1)->nullable()->comment("车窗防夹手功能");
				$table->char("fzwxgrbl", 1)->nullable()->comment("防紫外线/隔热玻璃");
				$table->char("hsjddtj", 1)->nullable()->comment("后视镜电动调节");
				$table->char("hsjjr", 1)->nullable()->comment("后视镜加热");
				$table->char("nwhsjzdfxm", 17)->nullable()->comment("内/外后视镜自动防眩目");
				$table->char("hsjddzd", 1)->nullable()->comment("后视镜电动折叠");
				$table->char("hsjjy", 1)->nullable()->comment("后视镜记忆");
				$table->char("hfdzyl", 1)->nullable()->comment("后风挡遮阳帘");
				$table->char("hpczyl", 1)->nullable()->comment("后排侧遮阳帘");
				$table->char("hpcysbl", 1)->nullable()->comment("后排侧隐私玻璃");
				$table->char("zybhzj", 1)->nullable()->comment("遮阳板化妆镜");
				$table->char("hys", 1)->nullable()->comment("后雨刷");
				$table->char("gyys", 1)->nullable()->comment("感应雨刷");
				$table->char("hpdlkt", 1)->nullable()->comment("后排独立空调");
				$table->char("hzcfk", 1)->nullable()->comment("后座出风口");
				$table->char("wdfqkz", 1)->nullable()->comment("温度分区控制");
				$table->char("cnkqtjhfgl", 1)->nullable()->comment("车内空气调节/花粉过滤");
				$table->char("czbx", 1)->nullable()->comment("车载冰箱");
				$table->char("sqxs", 4)->nullable()->comment("四驱形式");
				$table->char("cdxhlc", 3)->nullable()->comment("纯电续航里程");
				$table->char("hddjzdnjNm", 3)->nullable()->comment("后电动机最大扭矩(N·m)");
				$table->char("qddjs", 3)->nullable()->comment("驱动电机数");
				$table->char("gxbxhlck", 3)->nullable()->comment("工信部续航里程(km)");
				$table->char("zczdlx", 4)->nullable()->comment("驻车制动类型");
				$table->char("pljsts", 1)->nullable()->comment("疲劳驾驶提示");
				$table->char("zdjsjs", 1)->nullable()->comment("自动驾驶技术");
				$table->char("dcgyxj", 1)->nullable()->comment("电磁感应悬架");
				$table->char("ycqd", 1)->nullable()->comment("远程启动");
				$table->char("nzxcjly", 1)->nullable()->comment("内置行车记录仪");
				$table->char("zdjz", 1)->nullable()->comment("主动降噪");
				$table->char("sjwxcd", 1)->nullable()->comment("手机无线充电");
				$table->char("fjswhpktjan", 1)->nullable()->comment("副驾驶位后排可调节按钮");
				$table->char("qhpzyjr", 17)->nullable()->comment("前/后排座椅加热");
				$table->char("depdlzy", 1)->nullable()->comment("第二排独立座椅");
				$table->char("sjhlys", 1)->nullable()->comment("手机互联/映射");
				$table->char("clw", 1)->nullable()->comment("车联网");
				$table->char("czkqjhq", 1)->nullable()->comment("车载空气净化器");
				$table->char("jb", 6)->nullable()->comment("级别");
				$table->char("btgg", 4)->nullable()->comment("备胎规格");
				$table->char("lmtcnhsj", 1)->nullable()->comment("流媒体车内后视镜");
				$table->char("sc10hzdm", 5)->nullable()->comment("实测100-0km/h制动(m)");
				$table->char("scldjxm", 3)->nullable()->comment("实测离地间隙(mm)");
				$table->char("nylx", 7)->nullable()->comment("能源类型");
				$table->char("rlxs", 7)->nullable()->comment("燃料形式");
				$table->char("qhpzytf", 17)->nullable()->comment("前/后排座椅通风");
				$table->char("qhpzyam", 17)->nullable()->comment("前/后排座椅按摩");
				$table->char("gyfs", 4)->nullable()->comment("供油方式");
				$table->char("gtcl", 4)->nullable()->comment("缸体材料");
				$table->char("jgd", 9)->nullable()->comment("近光灯");
				$table->char("ygd", 9)->nullable()->comment("远光灯");
				$table->char("zllx", 6)->nullable()->comment("助力类型");
				$table->char("csjg", 5)->nullable()->comment("车身结构");
				$table->char("sc1hjss", 5)->nullable()->comment("实测0-100km/h加速(s)");
				$table->char("qddjzdnjNm", 3)->nullable()->comment("前电动机最大扭矩(N·m)");
				$table->char("xtzhglk", 3)->nullable()->comment("系统综合功率(kW)");
				$table->char("xtzhnjNm", 3)->nullable()->comment("系统综合扭矩(N·m)");
				$table->char("dwgs", 4)->nullable()->comment("挡位个数");
				$table->char("bsxlx", 14)->nullable()->comment("变速箱类型");
				$table->char("hpcmkqfs", 3)->nullable()->comment("后排车门开启方式");
				$table->char("qgsg", 2)->nullable()->comment("气缸数(个)");
				$table->char("mgqmsg", 2)->nullable()->comment("每缸气门数(个)");
				$table->char("djlx", 12)->nullable()->comment("电机类型");
				$table->char("djbj", 5)->nullable()->comment("电机布局");
				$table->char("jqxs", 7)->nullable()->comment("进气形式");
				$table->char("gjm", 5)->nullable()->comment("缸径(mm)");
				$table->char("ctjg", 4)->nullable()->comment("车体结构");
				$table->char("dczzb", 9)->nullable()->comment("电池组质保");
				$table->char("dspzy", 9)->nullable()->comment("第三排座椅");
				$table->char("zdnjNm", 4)->nullable()->comment("最大扭矩(N·m)");
				$table->char("zdmlP", 4)->nullable()->comment("最大马力(Ps)");
				$table->char("hpzyfdfs", 12)->nullable()->comment("后排座椅放倒方式");
				$table->char("zdglk", 5)->nullable()->comment("最大功率(kW)");
				$table->char("zczb", 9)->nullable()->comment("整车质保");
				$table->char("ggcl", 4)->nullable()->comment("缸盖材料");
				$table->char("bglhdlk1", 4)->nullable()->comment("百公里耗电量(kWh/100km)");
				$table->char("hltgg", 12)->nullable()->comment("后轮胎规格");
				$table->char("bsx", 18)->nullable()->comment("变速箱");
				$table->char("ddjzglk", 5)->nullable()->comment("电动机总功率(kW)");
				$table->char("jc", 18)->nullable()->comment("简称");
				$table->char("hbbz", 11)->nullable()->comment("环保标准");
				$table->char("dcrlk", 5)->nullable()->comment("电池容量(kWh)");
				$table->char("scyhL1", 5)->nullable()->comment("实测油耗(L/100km)");
				$table->char("ktkzfs", 19)->nullable()->comment("空调控制方式");
				$table->char("fxptj", 12)->nullable()->comment("方向盘调节");
				$table->char("ysqsl", 24)->nullable()->comment("扬声器数量");
				$table->char("dclx", 6)->nullable()->comment("电池类型");
				$table->char("wgys", 177)->nullable()->comment("外观颜色");
				$table->char("qdfs", 5)->nullable()->comment("驱动方式");
				$table->char("chm", 18)->nullable()->comment("侧滑门");
				$table->char("cszdjy", 13)->nullable()->comment("厂商指导价(元)");
				$table->char("qddjzdglk", 5)->nullable()->comment("前电动机最大功率(kW)");
				$table->char("hddjzdglk", 5)->nullable()->comment("后电动机最大功率(kW)");
				$table->char("plL", 4)->nullable()->comment("排量(L)");
				$table->char("hxjlx", 24)->nullable()->comment("后悬架类型");
				$table->char("nsys", 103)->nullable()->comment("内饰颜色");
				$table->char("xlxrjL", 12)->nullable()->comment("行李厢容积(L)");
				$table->char("kbxj", 12)->nullable()->comment("可变悬架");
				$table->char("zdzzzlk", 7)->nullable()->comment("最大载重质量(kg)");
				$table->char("qxjlx", 24)->nullable()->comment("前悬架类型");
				$table->char("yxrjL", 13)->nullable()->comment("油箱容积(L)");
				$table->char("kcdlbfb", 16)->nullable()->comment("快充电量百分比");
				$table->char("ysb", 5)->nullable()->comment("压缩比");
				$table->char("ysqpp", 41)->nullable()->comment("扬声器品牌");
				$table->char("zwsg", 11)->nullable()->comment("座位数(个)");
				$table->char("xcm", 5)->nullable()->comment("行程(mm)");
				$table->char("gf1hjss", 5)->nullable()->comment("官方0-100km/h加速(s)");
				$table->char("ccyjsj", 10)->nullable()->comment("车窗一键升降");
				$table->char("plm", 5)->nullable()->comment("排量(mL)");
				$table->char("ddjznjNm", 4)->nullable()->comment("电动机总扭矩(N·m)");
				$table->char("kcdl", 3)->nullable()->comment("快充电量(%)");
				$table->char("zktcsdpcc", 15)->nullable()->comment("中控台彩色大屏尺寸");
				$table->char("dtc", 3)->nullable()->comment("多天窗");
				$table->char("gxbzhyhL1", 5)->nullable()->comment("工信部综合油耗(L/100km)");
				$table->char("zycsqjg", 12)->nullable()->comment("中央差速器结构");
				$table->char("dccdsj", 18)->nullable()->comment("电池充电时间");
				$table->char("cxmc", 53)->nullable()->comment("车型名称");
				$table->char("fdjxh", 38)->nullable()->comment("发动机型号");
				$table->char("hxccm", 18)->nullable()->comment("货箱尺寸(mm)");
				$table->char("qqxhcsqcss", 5)->nullable()->comment("前桥限滑差速器/差速锁");
				$table->char("cdzjg", 12)->nullable()->comment("充电桩价格");
				$table->char("hqxhcsqcss", 12)->nullable()->comment("后桥限滑差速器/差速锁");
				$table->char("zdnjzsr", 10)->nullable()->comment("最大扭矩转速(rpm)");
				$table->char("CD", 22)->nullable()->comment("CD/DVD");
				$table->char("wjyyjk", 21)->nullable()->comment("外接音源接口");
				$table->char("ddj", 5)->nullable()->comment("电动机");
				$table->char("fdjtyjs", 39)->nullable()->comment("发动机特有技术");
				$table->char("kjrzlbj", 9)->nullable()->comment("可加热/制冷杯架");
				$table->char("fdj", 18)->nullable()->comment("发动机");
				$table->char("zycz", 18)->nullable()->comment("座椅材质");
				$table->char("qltgg", 13)->nullable()->comment("前轮胎规格");
			});
			echo "table car create".PHP_EOL;
		}
	}

	// 读取
	public static function move()
	{
		// 获取中文对应列
		$text_to_pinyin = json_decode(file_get_contents(PROJECT_APP_DOWN.'text_to_pinyin.json'),true);

		$empty = Capsule::table('raw_data')->where('status','readed')->get()->isEmpty();

		while (!$empty) {

			$datas = Capsule::table('raw_data')->where('status','readed')->limit(500)->get();

			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 拼接数据
				$temp = array(
					'brand' => $data->brand,
					'subbrand' => $data->subbrand,
					'series' => $data->series,
					'model' => $data->model,
					'md5_url' => $data->md5_url,
					'status' => 'wait',
					'url' => $data->url,
				);
				// 拼接车型参数字段
				foreach (json_decode($data->data,true) as $k => $v) {
					$temp = array_merge($temp,[$text_to_pinyin[$k] => $v]);
				}
				// 入库
				$empty = Capsule::table('car')->where('md5_url',$data->md5_url)->get()->isEmpty();
				if($empty) Capsule::table('car')->insert($temp);
				// 更新状态
				Capsule::table('raw_data')->where('id', $data->id)->update(['status' =>'moved']);
				// 输出
				echo 'raw_data '.$data->id."  moved successful!".PHP_EOL;
		    }
		    // 校验是否为空
			$empty = Capsule::table('raw_data')->where('status','readed')->get()->isEmpty();
		}
	}



	// 检测是否有js类名未转换
	public static function echoRejs()
	{		
		// 获取中文对应列
		$text_to_pinyin = json_decode(file_get_contents(PROJECT_APP_DOWN.'text_to_pinyin.json'),true);

		$empty = Capsule::table('raw_data')->where('status','readed')->get()->isEmpty();

		$all_id = array();

		while (!$empty) {

			$datas = Capsule::table('raw_data')->where('status','readed')->limit(500)->get();

			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	$json = json_decode($data->data,true);

		    	foreach ($json as $k => $v) {
		    		if(strpos($k,'class=') || strpos($k,'</span>') || strpos($v,'class=') || strpos($v,'</span>'))
		    		{
		    			echo $data->id.PHP_EOL;
		    			$all_id[] = $data->id;
		    			$all_url[] = $data->md5_url;
		    		}
		    	}
				// 更新状态
				Capsule::table('raw_data')->where('id', $data->id)->update(['status' =>'charge']);
				echo $data->id.PHP_EOL;
		    }
		    // 校验是否为空
			$empty = Capsule::table('raw_data')->where('status','readed')->get()->isEmpty();
		}

		file_put_contents(PROJECT_APP_DOWN.'all_re_run_id.json', implode(',', array_unique($all_id)));
		file_put_contents(PROJECT_APP_DOWN.'all_re_run_md5_urld.json', implode(',', array_unique($all_url)));
	}



	// 最终数据随机抽取检查
	public static function check()
	{

		$res = Capsule::table('car')->where('id',1500)->get();
		print_r(current($res));die;
	}


	// 整理数据
	public static function manage()
	{

		// 获取中文对应列
		$text_to_pinyin = json_decode(file_get_contents(PROJECT_APP_DOWN.'text_to_pinyin.json'),true);
		$pinyin_to_text = array_flip($text_to_pinyin);
		// param
		if(!Capsule::schema()->hasTable('param'))
		{
			Capsule::schema()->create('param', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('model_detail_id')->integer()->comment('车型ID');
			    $table->string('pram_cn')->string()->comment('参数中文名');
			    $table->string('pram_en')->string()->comment('参数字段名');
			    $table->string('pram_val')->string()->comment('参数值');
			});
			echo "table param create".PHP_EOL;
		}

		// 入库参数表
		$empty = Capsule::table('car')->where('status','wait')->get()->isEmpty();

		while (!$empty) {

			$datas = Capsule::table('car')->where('status','wait')->limit(200)->get();

			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 获取当前车型ID
		    	$model_detail = Capsule::table('model_detail')->where('md5_url',$data->md5_url)->get();
		    	$model_detail = $model_detail[0];

		    	$test = array();
		    	// 过滤空值
		    	foreach ($data as $k => $v) {
		    		$test[$k] = $v;
		    	}

		    	$test = array_filter($test);

		    	$temp = array();


		    	foreach ($test as $k => $v) {
		    		if(!empty($pinyin_to_text[$k]))
		    		{
			    		$temp[] = array(
			    			// 车型ID
			    			'model_detail_id' => $model_detail->id,
			    			'pram_cn' => $pinyin_to_text[$k],
			    			'pram_en' => $k,
			    			'pram_val' => $v
			    		);
		    		}
		    	}
				// 入库
				$empty = Capsule::table('param')->where('model_detail_id',$model_detail->id)->get()->isEmpty();
				if($empty) Capsule::table('param')->insert($temp);
				// 更新状态
				Capsule::table('car')->where('id', $data->id)->update(['status' =>'done']);
				// 输出
				echo 'car '.$data->id."  done successful!".PHP_EOL;
		    }
		    // 校验是否为空
			$empty = Capsule::table('car')->where('status','wait')->get()->isEmpty();
		}

	}



}
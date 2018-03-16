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
				preg_match_all('/<script>(.*?)<\/script>/s', file_get_contents($file), $matches);
				
				echo 'running phantomjs '.PHP_EOL;
				$class = array();
				$console = '$InsertRule$($index$, $item$){ console.log("\""+$GetClassName$($index$)+"\":\""+$item$+"\",");';
				// 循环每一段JavaScript代码并执行并获取结果
				foreach ($matches[1] as $v)
				{
					if(!strpos($v, 'InsertRule')) continue;
					// 加入console
					$str = preg_replace('/\$InsertRule\$\s+\(\$index\$,\s+\$item\$\)\s*{/',$console,$v);
					// 加入exit
					file_put_contents(PROJECT_APP_DOWN.'javascript.js', $str.' phantom.exit();');
					// 命令执行
					exec(APP_PATH.'/bin/phantomjs '.PROJECT_APP_DOWN.'javascript.js > '.PROJECT_APP_DOWN.'javascript.txt', $out, $status);
					// 读取文件并拼接为json
					$res = json_decode('{'.preg_replace('/,\s+$/', ' ', file_get_contents(PROJECT_APP_DOWN.'javascript.txt')).'}',true);
					// 去除类名的.
					foreach ($res as $k => $v) { $class[ltrim($k,'.')] = $v; }
				}
				echo 'catch class'.PHP_EOL;
				// config
				preg_match_all('/var\s*config\s*=(.*?});/', file_get_contents($file), $matches);
				$config = json_decode(current($matches[1]),true);
				$newConfig = array();
				foreach ($config['result']['paramtypeitems'] as $k => $v) {
					foreach ($v['paramitems'] as $kk => $vv) {
						$newConfig[$vv['name']] = $vv['valueitems'][0]['value'];
					}
				}

				// 替换
				$config = array();
				foreach ($newConfig as $k => $v) {
					$kkk = '';
					$vvv = '';
					foreach ($class as $kk => $vv) {
	    				// 分别替换健名和键值对应的类名
	    				if($kkk!='') $k=$kkk;if($vvv!='') $v=$vvv;
						$kkk = preg_replace("/<span\s*class='".$kk."'><\/span>/",$vv,$k);
	    				$vvv = preg_replace("/<span\s*class='".$kk."'><\/span>/",$vv,$v);
	    			}
	    			$config[$kkk] = $vvv; 
    			}

    			echo 'catch config'.PHP_EOL;
				// option
				preg_match_all('/var\s*option\s*=(.*?});/', file_get_contents($file), $matches);
				$option = json_decode(current($matches[1]),true);
				$newOption = array();
				foreach ($option['result']['configtypeitems'] as $k => $v)
				{
					foreach ($v['configitems'] as $kk => $vv) {
						$newOption[$vv['name']] = $vv['valueitems'][0]['value'];
					}
				}
				// 替换
				$option = array();
				foreach ($newOption as $k => $v) {
					$kkk = '';
					$vvv = '';
					foreach ($class as $kk => $vv) {
	    				// 分别替换健名和键值对应的类名
	    				if($kkk!='') $k=$kkk;if($vvv!='') $v=$vvv;
						$kkk = preg_replace("/<span\s*class='".$kk."'><\/span>/",$vv,$k);
	    				$vvv = preg_replace("/<span\s*class='".$kk."'><\/span>/",$vv,$v);
	    			}
	    			$option[$kkk] = $vvv; 
    			}
    			echo 'catch option'.PHP_EOL;
				// color
				preg_match_all('/var\s*color\s*=(.*?});/', file_get_contents($file), $matches);
				$color = json_decode(current($matches[1]),true);
				$newColor = implode(',',array_column($color['result']['specitems'][0]['coloritems'], 'name'));
				$newColor = ['外观颜色'=>$newColor];
				// innerColor
				preg_match_all('/var\s*innerColor\s*=(.*?});/', file_get_contents($file), $matches);
				$innerColor = json_decode(current($matches[1]),true);
				$newInnerColor = implode(',',array_column($innerColor['result']['specitems'][0]['coloritems'],'name')) ;
				$newInnerColor = ['内饰颜色' => $newInnerColor]; 

				// 拼接所有数组
				$test = array_merge($config,$option,$newColor,$newInnerColor);

				// 先存储于数据库之中-转json
				$temp = array();

				$temp = array(
					'brand' => $data->brand,
					'subbrand' => $data->subbrand,
					'series' => $data->series,
					'model' => $data->model,
					'md5_url' => $data->md5_url,
					'url' => $data->url,
					'data' => json_encode($test)
				);
				// raw_data
				$empty = Capsule::table('raw_data')->where('md5_url',$data->md5_url)->get()->isEmpty();
				if($empty) $car_id = Capsule::table('raw_data')->insert($temp);
				// 更新状态
				Capsule::table('model_detail')->where('id', $data->id)->update(['status' =>'readed']);
				// 命令行执行时候不需要经过apache直接输出在窗口
				echo 'model_detail '.$data->id.'.html'."  analyse successful!".PHP_EOL; 

				continue;

		    	// 是否存在
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
							'md5_url' => $data->md5_url,
							'url' => $data->url
						);
						// 厂商
						$temp['changshang'] = isset($test['厂商'])?$test['厂商']:'';
						// 级别
						$temp['jibei'] = isset($test['级别'])?$test['级别']:'';
						// 整车质保
						$temp['zhengchezhibao'] = isset($test['整车质保'])?$test['整车质保']:'';

						// 能源类型
						$temp['nengyuanleixing'] = isset($test['能源类型'])?$test['能源类型']:'';
						// 上市时间
						$temp['shangshishijian'] = isset($test['上市时间'])?$test['上市时间']:'';
						// 最大功率（kw）
						$temp['zuidagonglv'] = isset($test['最大功率(kW)'])?$test['最大功率(kW)']:'';
						// 最大扭矩（N·m）
						$temp['zuidaniuju'] = isset($test['最大扭矩(N·m)'])?$test['最大扭矩(N·m)']:'';
						// 发动机
						$temp['faodngji'] = isset($test['发动机'])?$test['发动机']:'';
						// 变速箱
						$temp['biansuxiang'] = isset($test['变速箱'])?$test['变速箱']:'';
						// 长*宽*高
						$temp['changkuangao'] = isset($test['长*宽*高(mm)'])?$test['长*宽*高(mm)']:'';
						// 车身结构
						$temp['cheshenjiegou'] = isset($test['车身结构'])?$test['车身结构']:'';
						// 最高车速
						$temp['zuigaochesu'] = isset($test['最高车速(km/h)'])?$test['最高车速(km/h)']:'';
						// 官方0-100km/h加速(s)
						$temp['guanfangjiasu'] = isset($test['官方0-100km/h加速(s)'])?$test['官方0-100km/h加速(s)']:'';
						// 实测0-100km/s加速
						$temp['shicejiasu'] = isset($test['实测0-100km/h加速(s)'])?$test['实测0-100km/h加速(s)']:'';
						// 实测100km/s-o制动(m)
						$temp['shicezhidong'] = isset($test['实测100-0km/h制动(m)'])?$test['实测100-0km/h制动(m)']:'';
						// 实测离地间隙（mm）
						$temp['shicelidijianxi'] = isset($test['实测离地间隙(mm)'])?$test['实测离地间隙(mm)']:'';
						// 工信部综合油耗（L/100km）
						$temp['gongxinbuyouhao'] = isset($test['工信部综合油耗(L/100km)'])?$test['工信部综合油耗(L/100km)']:'';
						// 实测油耗(L/100km)
						$temp['shiceyouhao'] = isset($test['实测油耗(L/100km)'])?$test['实测油耗(L/100km)']:'';

						// car_basic
						$empty = Capsule::table('car_basic')->where('md5_url',$data->md5_url)->get()->isEmpty();
						if($empty) $car_id = Capsule::table('car_basic')->insertGetId($temp);
						

						// 入库车身信息表
						$temp = array('car_id' => $car_id);
						// 长度
						$temp['cheshenchangdu'] = isset($test['长度(mm)'])?$test['长度(mm)']:'';
						// 宽度
						$temp['cheshenkuandu'] = isset($test['宽度(mm)'])?$test['宽度(mm)']:'';
						// 高度
						$temp['cheshengaodu'] = isset($test['高度(mm)'])?$test['高度(mm)']:'';
						// 轴距
						$temp['cheshenzhouju'] = isset($test['轴距(mm)'])?$test['轴距(mm)']:'';
						// 前轮距
						$temp['cheshenqianlunju'] = isset($test['前轮距(mm)'])?$test['前轮距(mm)']:'';
						// 后轮距
						$temp['cheshenhoulunju'] = isset($test['后轮距(mm)'])?$test['后轮距(mm)']:'';
						// 最小离地间隙
						$temp['cheshenzuixiaolidijianxi'] = isset($test['最小离地间隙(mm)'])?$test['最小离地间隙(mm)']:'';
						// 车身结构
						$temp['cheshencheshenjiegou'] = isset($test['车身结构'])?$test['车身结构']:'';
						// 车门数
						$temp['cheshenchemenshu'] = isset($test['车门数(个)'])?$test['车门数(个)']:'';
						// 座位数
						$temp['cheshenzuoweishu'] = isset($test['座位数(个)'])?$test['座位数(个)']:'';
						// 油箱容积
						$temp['cheshenyouxiangrongji'] = isset($test['油箱容积(L)'])?$test['油箱容积(L)']:'';
						// 行李箱容积
						$temp['xinglixiangrongji'] = isset($test['行李厢容积(L)'])?$test['行李厢容积(L)']:'';
						// 整备质量（kg）
						$temp['zhengbeizhiliang'] = isset($test['整备质量(kg)'])?$test['整备质量(kg)']:'';

						// car_body
						$empty = Capsule::table('car_body')->where('car_id',$car_id)->get()->isEmpty();
						Capsule::table('car_body')->insert(array_map('trim',$temp));

						// 发动机信息
						$temp = array('car_id' => $car_id);
						// 供油方式
						$temp['gongyoufangshi'] = isset($test['供油方式'])?$test['供油方式']:'';
						// 缸盖材料
						$temp['ganggaicailiao'] = isset($test['缸盖材料'])?$test['缸盖材料']:'';
						// 缸体材料
						$temp['gangticailiao'] = isset($test['缸体材料'])?$test['缸体材料']:'';
						// 环保标准
						$temp['huanbaobiaozhun'] = isset($test['环保标准'])?$test['环保标准']:'';
						// 进气形式
						$temp['jingqixingshi'] = isset($test['进气形式'])?$test['进气形式']:'';

						// 型号
						$temp['fadongjixinghao'] = isset($test['发动机型号'])?$test['发动机型号']:'';
						// 排量ml
						$temp['pailiangml'] = isset($test['排量(mL)'])?$test['排量(mL)']:'';
						// 排量L
						$temp['pailiangl'] = isset($test['排量(L)'])?$test['排量(L)']:'';
						// 气缸排列形式
						$temp['qigangpailiexingshi'] = isset($test['气缸排列形式'])?$test['气缸排列形式']:'';
						// 气缸数
						$temp['qigangshu'] = isset($test['气缸数(个)'])?$test['气缸数(个)']:'';
						// 每钢气门数
						$temp['meigangqimenshu'] = isset($test['每缸气门数(个)'])?$test['每缸气门数(个)']:'';
						// 压缩比
						$temp['yasuobi'] = isset($test['压缩比'])?$test['压缩比']:'';
						// 配气机构
						$temp['peiqijiegou'] = isset($test['配气机构'])?$test['配气机构']:'';
						// 缸径
						$temp['gangjing'] = isset($test['缸径(mm)'])?$test['缸径(mm)']:'';
						// 行程
						$temp['xingcheng'] = isset($test['行程(mm)'])?$test['行程(mm)']:'';
						// 最大马力
						$temp['zuidamali'] = isset($test['最大马力(Ps)'])?$test['最大马力(Ps)']:'';
						// 发动机最大功率
						$temp['fadongjizuidagonglv'] = isset($test['最大功率(kW)'])?$test['最大功率(kW)']:'';
						// 最大功率转速
						$temp['zuidagonglvzhuansu'] = isset($test['最大功率转速(rpm)'])?$test['最大功率转速(rpm)']:'';
						// 发动机最大扭距
						$temp['fadongjizuidaniuju'] = isset($test['最大扭矩(N·m)'])?$test['最大扭矩(N·m)']:'';
						// 最大扭距转数
						$temp['zuidaniujuzhuansu'] = isset($test['最大扭矩转速(rpm)'])?$test['最大扭矩转速(rpm)']:'';
						// 特有技术
						$temp['teyoujishu'] = isset($test['发动机特有技术'])?$test['发动机特有技术']:'';
						// 燃料形式
						$temp['ranliaoxingshi'] = isset($test['燃料形式'])?$test['燃料形式']:'';
						// 燃油标号
						$temp['ranyoubianhao'] = isset($test['燃油标号'])?$test['燃油标号']:'';
						// car_engine
						$empty = Capsule::table('car_engine')->where('car_id',$car_id)->get()->isEmpty();

						Capsule::table('car_engine')->insert(array_map('trim', $temp));


						// 变速箱+底盘转向+车轮制动
						$temp = array('car_id' => $car_id);
						// 底盘驱动方式
						$temp['dipanqudongfangshi'] = isset($test['驱动方式'])?$test['驱动方式']:'';
						// 前悬挂类型
						$temp['qianxuangualeixing'] = isset($test['前悬架类型'])?$test['前悬架类型']:'';
						// 后悬挂类型
						$temp['houxuangualeixing'] = isset($test['后悬架类型'])?$test['后悬架类型']:'';
						// 助力类型
						$temp['zhulileixing'] = isset($test['助力类型'])?$test['助力类型']:'';
						// 车体结构
						$temp['chetijiegou'] = isset($test['车体结构'])?$test['车体结构']:'';

						// 档位个数
						$temp['dangweigeshu'] = isset($test['挡位个数'])?$test['挡位个数']:'';
						// 变速箱类型
						$temp['biansuxiangleixing'] = isset($test['变速箱类型'])?$test['变速箱类型']:'';
						// 简称
						$temp['biansuxiangjiancheng'] = isset($test['简称'])?$test['简称']:'';
						// 前制动器类型
						$temp['qianzhidongqileixing'] = isset($test['前制动器类型'])?$test['前制动器类型']:'';
						// 后制动器类型
						$temp['houzhidongqileixing'] = isset($test['后制动器类型'])?$test['后制动器类型']:'';
						// 驻车制动类型
						$temp['zhuchezhidongleixing'] = isset($test['驻车制动类型'])?$test['驻车制动类型']:'';
						// 前轮胎规格
						$temp['qianluntaiguige'] = isset($test['前轮胎规格'])?$test['前轮胎规格']:'';
						// 后轮胎规格
						$temp['houluntaiguige'] = isset($test['后轮胎规格'])?$test['后轮胎规格']:'';
						// 备胎规格
						$temp['beitaiguige'] = isset($test['备胎规格'])?$test['备胎规格']:'';
						// 主副驾座安全气囊
						$temp['zhufujiazuoanquanqinang'] = isset($test['主/副驾驶座安全气囊'])?$test['主/副驾驶座安全气囊']:'';
						// 前后侧拍气囊
						$temp['qianhoucepaiqinang'] = isset($test['前/后排侧气囊'])?$test['前/后排侧气囊']:'';
						// 前后排头部气囊
						$temp['qianhoupaitoubuqinang'] = isset($test['前/后排头部气囊(气帘)'])?$test['前/后排头部气囊(气帘)']:'';
						// 膝部气囊
						$temp['xibuqinang'] = isset($test['膝部气囊'])?$test['膝部气囊']:'';
						// 胎压监测装置
						$temp['taiyajiancezhuangzhi'] = isset($test['胎压监测装置'])?$test['胎压监测装置']:'';
						// 零胎压继续行驶
						$temp['lingtaiyajixuxingshi'] = isset($test['零胎压继续行驶'])?$test['零胎压继续行驶']:'';
						// 安全带维系提示
						$temp['anquandaiweixitishi'] = isset($test['安全带未系提示'])?$test['安全带未系提示']:'';
						// 儿童座椅接口
						$temp['ertongzuoyijiekou'] = isset($test['ISOFIX儿童座椅接口'])?$test['ISOFIX儿童座椅接口']:'';
						// abs防抱死
						$temp['absfangbaosi'] = isset($test['ABS防抱死'])?$test['ABS防抱死']:'';
						// 制动力分配
						$temp['zhidonglifenpei'] = isset($test['制动力分配(EBD/CBC等)'])?$test['制动力分配(EBD/CBC等)']:'';
						// 刹车辅助
						$temp['shachefuzhu'] = isset($test['刹车辅助(EBA/BAS/BA等)'])?$test['刹车辅助(EBA/BAS/BA等)']:'';
						// 牵引力控制
						$temp['qianyinlikongzhi'] = isset($test['牵引力控制(ASR/TCS/TRC等)'])?$test['牵引力控制(ASR/TCS/TRC等)']:'';
						// 车身稳定控制
						$temp['cheshenwendingkongzhi'] = isset($test['车身稳定控制(ESC/ESP/DSC等)'])?$test['车身稳定控制(ESC/ESP/DSC等)']:'';
						// 并线辅助
						$temp['bingxianfuzhu'] = isset($test['并线辅助'])?$test['并线辅助']:'';
						// 车道偏离预警
						$temp['chedaopianliyujing'] = isset($test['车道偏离预警系统'])?$test['车道偏离预警系统']:'';
						// 主被动安全系统
						$temp['zhubeidonganquanxitong'] = isset($test['主动刹车/主动安全系统'])?$test['主动刹车/主动安全系统']:'';
						// 夜视系统
						$temp['yeshixitong'] = isset($test['夜视系统'])?$test['夜视系统']:'';
						// 疲劳驾驶
						$temp['pilaojiashi'] = isset($test['疲劳驾驶提示'])?$test['疲劳驾驶提示']:'';

						// car_gearbox
						$empty = Capsule::table('car_gearbox')->where('car_id',$car_id)->get()->isEmpty();
						Capsule::table('car_gearbox')->insert(array_map('trim',$temp));


						// 操控配置和防盗配置car_configure
						$temp = array('car_id' => $car_id);
						// 前后驻车雷达
						$temp['qianhouzhucheleida'] = isset($test['前/后驻车雷达'])?$test['前/后驻车雷达']:'';
						// 方向盘调节
						$temp['fangxiangpantiaojie'] = isset($test['方向盘调节'])?$test['方向盘调节']:'';
						// 倒车视屏影像
						$temp['daocheshipingyingxiang'] = isset($test['倒车视频影像'])?$test['倒车视频影像']:'';
						// 可变转向化
						$temp['kebianzhuanxianghua'] = isset($test['可变转向比'])?$test['可变转向比']:'';
						// 无钥匙进入系统
						$temp['wuyaoshijinruxitong'] = isset($test['无钥匙进入系统'])?$test['无钥匙进入系统']:'';

						/*********************************************************************************************************/
						// 全景摄像头
						$temp['quanjingshexiangtou'] = isset($test['全景摄像头'])?$test['全景摄像头']:'';
						// 定速巡航
						$temp['dingsuxunhang'] = isset($test['定速巡航'])?$test['定速巡航']:'';
						// 自适应巡航
						$temp['zishiyingxunhang'] = isset($test['自适应巡航'])?$test['自适应巡航']:'';
						// 自动泊车
						$temp['zidongboche'] = isset($test['自动泊车入位'])?$test['自动泊车入位']:'';
						// 发动机启停
						$temp['fadongjiqiting'] = isset($test['发动机启停技术'])?$test['发动机启停技术']:'';
						// 自动驾驶
						$temp['zidongjiashi'] = isset($test['自动驾驶技术'])?$test['自动驾驶技术']:'';
						// 上坡辅助
						$temp['shangpofuzhu'] = isset($test['上坡辅助'])?$test['上坡辅助']:'';
						// 自动驻车
						$temp['zidongzhuche'] = isset($test['自动驻车'])?$test['自动驻车']:'';
						// 陡坡缓降
						$temp['doupohuanjiang'] = isset($test['陡坡缓降'])?$test['陡坡缓降']:'';
						// 可变悬挂
						$temp['kebianxuangua'] = isset($test['可变悬架'])?$test['可变悬架']:'';
						// 空气悬挂
						$temp['kongqixuangua'] = isset($test['空气悬架'])?$test['空气悬架']:'';
						// 电磁感应悬挂
						$temp['dianciganyingxuangua'] = isset($test['电磁感应悬架'])?$test['电磁感应悬架']:'';
						// 前桥差速器
						$temp['qianqiaochasuqi'] = isset($test['前桥限滑差速器/差速锁'])?$test['前桥限滑差速器/差速锁']:'';
						// 中央差速器
						$temp['zhongyangchasuqi'] = isset($test['中央差速器锁止功能'])?$test['中央差速器锁止功能']:'';
						// 后桥差速器
						$temp['houqiaochasuqi'] = isset($test['后桥限滑差速器/差速锁'])?$test['后桥限滑差速器/差速锁']:'';
						// 整车主动转向
						$temp['zhengchezhudongzhuanxiang'] = isset($test['整体主动转向系统'])?$test['整体主动转向系统']:'';
						// 电动天窗
						$temp['diandongtianchuang'] = isset($test['电动天窗'])?$test['电动天窗']:'';
						// 全景天窗
						$temp['quanjingtianchuang'] = isset($test['全景天窗'])?$test['全景天窗']:'';
						// 多天窗
						$temp['duotianchuang'] = isset($test['多天窗'])?$test['多天窗']:'';
						// 运动外观套件
						$temp['yundongwaiguantaojian'] = isset($test['运动外观套件'])?$test['运动外观套件']:'';
						// 铝合金轮圈
						$temp['lvhejinlunquan'] = isset($test['铝合金轮圈'])?$test['铝合金轮圈']:'';
						// 电动吸合门
						$temp['diandongxihemen'] = isset($test['电动吸合门'])?$test['电动吸合门']:'';
						// 侧滑门
						$temp['cehuamen'] = isset($test['侧滑门'])?$test['侧滑门']:'';
						// 电动后备箱
						$temp['diandonghoubeixiang'] = isset($test['电动后备厢'])?$test['电动后备厢']:'';
						// 感应后备箱
						$temp['ganyinghoubeixiang'] = isset($test['感应后备厢'])?$test['感应后备厢']:'';
						// 车顶行李架
						$temp['chedingxinglijia'] = isset($test['车顶行李架'])?$test['车顶行李架']:'';
						// 发动机电子防盗
						$temp['fadongjidianzifangdao'] = isset($test['发动机电子防盗'])?$test['发动机电子防盗']:'';
						// 车内中控锁
						$temp['cheneizhongkongsuo'] = isset($test['车内中控锁'])?$test['车内中控锁']:'';
						// 遥控钥匙
						$temp['yaokongyaoshi'] = isset($test['遥控钥匙'])?$test['遥控钥匙']:'';
						// 无钥匙启动系统
						$temp['wuyaoshiqidongxitong'] = isset($test['无钥匙启动系统'])?$test['无钥匙启动系统']:'';
						// 远程启动
						$temp['yuanchengqidong'] = isset($test['远程启动'])?$test['远程启动']:'';
						// 皮质方向盘
						$temp['pizhifangxiangpan'] = isset($test['皮质方向盘'])?$test['皮质方向盘']:'';
						// 方向盘电动
						$temp['fangxiangpandiandong'] = isset($test['方向盘电动调节'])?$test['方向盘电动调节']:'';
						// 多功能方向盘
						$temp['duogongnengfangxiangpan'] = isset($test['多功能方向盘'])?$test['多功能方向盘']:'';
						// 方向盘换挡
						$temp['fangxiangpanhuandang'] = isset($test['方向盘换挡'])?$test['方向盘换挡']:'';
						// 方向盘加热
						$temp['fangxiangpanjiare'] = isset($test['方向盘加热'])?$test['方向盘加热']:'';
						// 方向盘记忆
						$temp['fangxiangpanjiyi'] = isset($test['方向盘记忆'])?$test['方向盘记忆']:'';
						// 行车电脑显示屏
						$temp['xingchediannaoxianshiping'] = isset($test['行车电脑显示屏'])?$test['行车电脑显示屏']:'';
						// 全液晶仪表盘
						$temp['quanyejingyibiaopan'] = isset($test['全液晶仪表盘'])?$test['全液晶仪表盘']:'';
						// 互动抬头数字显示
						$temp['hudtaitoushuzixianshi'] = isset($test['HUD抬头数字显示'])?$test['HUD抬头数字显示']:'';
						// 内置行车记录仪
						$temp['neizhixingchejiluyi'] = isset($test['内置行车记录仪'])?$test['内置行车记录仪']:'';
						// 主动降噪
						$temp['zhudongjiangzao'] = isset($test['主动降噪'])?$test['主动降噪']:'';
						// 手机无线充电
						$temp['shoujiwuxianchongdian'] = isset($test['手机无线充电'])?$test['手机无线充电']:'';
										
						$empty = Capsule::table('car_configure')->where('car_id',$car_id)->get()->isEmpty();
						Capsule::table('car_configure')->insert(array_map('trim',$temp));


						// 座椅配置和多媒体配置
						$temp = array('car_id' => $car_id);
						// 座椅材质
						$temp['zuoyicaizhi'] = isset($test['座椅材质'])?$test['座椅材质']:'';
						// 后排座椅放倒方式
						$temp['houpaizuoyifangdaofangshi'] = isset($test['后排座椅放倒方式'])?$test['后排座椅放倒方式']:'';
						// 前后中央扶手
						$temp['qianhouzhongyangfushou'] = isset($test['前/后中央扶手'])?$test['前/后中央扶手']:'';
						// 运动座椅风格
						$temp['yundongfenggezuoyi'] = isset($test['运动风格座椅'])?$test['运动风格座椅']:'';
						// 腰部支撑调节
						$temp['yaobuzhichengtiaojie'] = isset($test['腰部支撑调节'])?$test['腰部支撑调节']:'';
						// 手机互联映射
						$temp['shoujihulianyingshe'] = isset($test['手机互联/映射'])?$test['手机互联/映射']:'';

						/*********************************************************************************************************/
						// 座椅高低调节 
						$temp['zuoyigaoditiaojie'] = isset($test['座椅高低调节'])?$test['座椅高低调节']:'';
						// 肩部支撑调节
						$temp['jianbuzhichengtiaojie'] = isset($test['肩部支撑调节'])?$test['肩部支撑调节']:'';
						// 主副驾座电动调节
						$temp['zhufujiazuodiandongtiaojie'] = isset($test['主/副驾驶座电动调节'])?$test['主/副驾驶座电动调节']:'';
						// 第二排角度调节
						$temp['dierpaijiaodutiaojie'] = isset($test['第二排靠背角度调节'])?$test['第二排靠背角度调节']:'';
						// 第二排座椅移动
						$temp['dierpaizuoyiyidong'] = isset($test['第二排座椅移动'])?$test['第二排座椅移动']:'';
						// 后排座椅电动
						$temp['houpaizuoyidiandong'] = isset($test['后排座椅电动调节'])?$test['后排座椅电动调节']:'';
						// 副驾驶后排可调节
						$temp['fujiashihoupaiketiaojie'] = isset($test['副驾驶位后排可调节按钮'])?$test['副驾驶位后排可调节按钮']:'';
						// 电动座椅记忆
						$temp['diandongzuoyijiyi'] = isset($test['电动座椅记忆'])?$test['电动座椅记忆']:'';
						// 前后座椅加热
						$temp['qianhouzuoyijiare'] = isset($test['前/后排座椅加热'])?$test['前/后排座椅加热']:'';
						// 前后座椅通风
						$temp['qianhouzuoyitongfeng'] = isset($test['前/后排座椅通风'])?$test['前/后排座椅通风']:'';
						// 前后座椅按摩
						$temp['qianhouzuoyianmo'] = isset($test['前/后排座椅按摩'])?$test['前/后排座椅按摩']:'';
						// 第二排独立座椅
						$temp['dierpaidulizuoyi'] = isset($test['第二排独立座椅'])?$test['第二排独立座椅']:'';
						// 第三排座椅
						$temp['disanpaizuoyi'] = isset($test['第三排座椅'])?$test['第三排座椅']:'';
						// 后排杯架
						$temp['houpaibeijia'] = isset($test['后排杯架'])?$test['后排杯架']:'';
						// 加热制冷杯架
						$temp['jiarezhilengbeijia'] = isset($test['可加热/制冷杯架'])?$test['可加热/制冷杯架']:'';
						// gps导航服务
						$temp['gpsdaohangfuwu'] = isset($test['GPS导航系统'])?$test['GPS导航系统']:'';
						// 定位互动
						$temp['dingweihudong'] = isset($test['定位互动服务'])?$test['定位互动服务']:'';
						// 中控台彩色大屏
						$temp['zhongkongtaicaisedaping'] = isset($test['中控台彩色大屏'])?$test['中控台彩色大屏']:'';
						// 中控台彩色大屏尺寸
						$temp['zhongkongtaicaisedapingchicun'] = isset($test['中控台彩色大屏尺寸'])?$test['中控台彩色大屏尺寸']:'';
						// 中控液晶屏分屏显示
						$temp['zhongkongyejingpingfenpingxianshi'] = isset($test['中控液晶屏分屏显示'])?$test['中控液晶屏分屏显示']:'';
						// 蓝牙车载电话
						$temp['lanyachezaidianhua'] = isset($test['蓝牙/车载电话'])?$test['蓝牙/车载电话']:'';
						// 车联网
						$temp['chelianwang'] = isset($test['车联网'])?$test['车联网']:'';
						// 车载电视
						$temp['chezaidianshi'] = isset($test['车载电视'])?$test['车载电视']:'';
						// 后排液晶屏
						$temp['houpaiyejingping'] = isset($test['后排液晶屏'])?$test['后排液晶屏']:'';
						// 220v/230v电源
						$temp['dianyuan'] = isset($test['220V/230V电源'])?$test['220V/230V电源']:'';
						// 音源接口
						$temp['yinyuanjiekou'] = isset($test['外接音源接口'])?$test['外接音源接口']:'';
						// cddvd
						$temp['cddvd'] = isset($test['CD/DVD'])?$test['CD/DVD']:'';
						
						// 扬声器品牌
						$temp['yangshengqipinpai'] = isset($test['扬声器品牌'])?$test['扬声器品牌']:'';
						// 扬声器数量
						$temp['yangshengqishuliang'] = isset($test['扬声器数量'])?$test['扬声器数量']:'';

						$empty = Capsule::table('car_chair')->where('car_id',$car_id)->get()->isEmpty();
						Capsule::table('car_chair')->insert(array_map('trim',$temp));

					

						$temp = array('car_id' => $car_id);
						// 近光灯
						$temp['jinguangdeng'] = isset($test['近光灯'])?$test['近光灯']:'';
						// 远光灯
						$temp['yuanguangdeng'] = isset($test['远光灯'])?$test['远光灯']:'';
						// 前后电动车窗
						$temp['qianhoudiandongchechuang'] = isset($test['前/后电动车窗'])?$test['前/后电动车窗']:'';
						// 外观颜色
						$temp['waiguanyanse'] = isset($test['外观颜色'])?$test['外观颜色']:'';
						// 内饰颜色
						$temp['neishiyanse'] = isset($test['内饰颜色'])?$test['内饰颜色']:'';

						// 防紫外线/隔热玻璃
						$temp['fangziwaixiangereboli'] = isset($test['防紫外线/隔热玻璃'])?$test['防紫外线/隔热玻璃']:'';

						/*********************************************************************************************************/
						// 日间行车灯
						$temp['rijianxingchedeng'] = isset($test['LED日间行车灯'])?$test['LED日间行车灯']:'';
						// 自适应远近光
						$temp['zishiyingyuanjinguang'] = isset($test['自适应远近光'])?$test['自适应远近光']:'';
						// 自动头灯
						$temp['zidongtoudeng'] = isset($test['自动头灯'])?$test['自动头灯']:'';
						// 转向辅助灯
						$temp['zhuanxiangfuzhudeng'] = isset($test['转向辅助灯'])?$test['转向辅助灯']:'';
						// 转向头灯
						$temp['zhuanxiangtoudeng'] = isset($test['转向头灯'])?$test['转向头灯']:'';
						// 前雾灯
						$temp['qianwudeng'] = isset($test['前雾灯'])?$test['前雾灯']:'';
						// 大灯高度可调
						$temp['dadenggaoduketiao'] = isset($test['大灯高度可调'])?$test['大灯高度可调']:'';
						// 大灯清洗装置
						$temp['dadengqingxizhuangzhi'] = isset($test['大灯清洗装置'])?$test['大灯清洗装置']:'';
						// 车内氛围灯
						$temp['cheneifenweideng'] = isset($test['车内氛围灯'])?$test['车内氛围灯']:'';
						// 车窗一键升降
						$temp['chechuangyijianshengjiang'] = isset($test['车窗一键升降'])?$test['车窗一键升降']:'';
						// 车窗防夹手功能
						$temp['chechuangfangjiashou'] = isset($test['车窗防夹手功能'])?$test['车窗防夹手功能']:'';
						// 后视镜电动调节
						$temp['houshijingdiandongtiaojie'] = isset($test['后视镜电动调节'])?$test['后视镜电动调节']:'';
						// 后视镜加热
						$temp['houshijingjiare'] = isset($test['后视镜加热'])?$test['后视镜加热']:'';
						// 后视镜自动防眩目
						$temp['houshijingzidongfangxuanmu'] = isset($test['内/外后视镜自动防眩目'])?$test['内/外后视镜自动防眩目']:'';
						// 流媒体车内后视镜
						$temp['liumeiticheneihoushijing'] = isset($test['流媒体车内后视镜'])?$test['流媒体车内后视镜']:'';
						// 后视镜电动折叠
						$temp['houshijingdiandongzhedie'] = isset($test['后视镜电动折叠'])?$test['后视镜电动折叠']:'';
						// 后视镜记忆
						$temp['houshijingjiyi'] = isset($test['后视镜记忆'])?$test['后视镜记忆']:'';
						// 后风挡遮阳帘
						$temp['houfengdangzheyanglian'] = isset($test['后风挡遮阳帘'])?$test['后风挡遮阳帘']:'';
						// 后排侧遮阳帘
						$temp['houpaicezheyanglian'] = isset($test['后排侧遮阳帘'])?$test['后排侧遮阳帘']:'';
						// 后排侧隐私玻璃
						$temp['houpaiceyinsiboli'] = isset($test['后排侧隐私玻璃'])?$test['后排侧隐私玻璃']:'';
						// 遮阳板化妆镜
						$temp['zheyangbanhuazhuangjing'] = isset($test['遮阳板化妆镜'])?$test['遮阳板化妆镜']:'';
						// 后雨刷
						$temp['houyushua'] = isset($test['后雨刷'])?$test['后雨刷']:'';
						// 感应雨刷
						$temp['ganyingyushua'] = isset($test['感应雨刷'])?$test['感应雨刷']:'';
						// 空调控制方式
						$temp['kongtiaokongzhifangshi'] = isset($test['空调调控方式'])?$test['空调调控方式']:'';
						// 后排独立空调
						$temp['houpaidulikongtiao'] = isset($test['后排独立空调'])?$test['后排独立空调']:'';
						// 后座出风口
						$temp['houzuochufengkou'] = isset($test['后座出风口'])?$test['后座出风口']:'';
						// 温度分区控制
						$temp['wendufenqukongzhi'] = isset($test['温度分区控制'])?$test['温度分区控制']:'';
						// 花粉过滤
						$temp['huafenguolv'] = isset($test['车内空气调节/花粉过滤'])?$test['车内空气调节/花粉过滤']:'';
						// 车载空气净化器
						$temp['chezaikongqijinghuaqi'] = isset($test['车载空气净化器'])?$test['车载空气净化器']:'';
						// 车载冰箱
						$temp['chezaibingxiang'] = isset($test['车载冰箱'])?$test['车载冰箱']:'';

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
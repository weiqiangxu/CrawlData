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
  * 清洗数据
  * @author xu
  * @copyright 2018/01/24
  */
class fivestep{

	public static function initable()
	{
		// 数据库迁移类对象
		$Schema = Capsule::connection('model_jdcswww')->getSchemaBuilder();
		
		// 汽车类型表
		if(!$Schema->hasTable('data_cars'))
		{
			$Schema->create('data_cars', function (Blueprint $table) {
			    $table->increments('car_id');
			    $table->string('car_text')->comment('汽车类型');
			});
			echo "init table data_cars!\r\n";
		}

		// 引擎表
		if(!$Schema->hasTable('data_engine'))
		{
			$Schema->create('data_engine', function (Blueprint $table) {
			    $table->increments('eng_id');
			    $table->integer('engine_jml_id')->nullable()->comment('品牌车型ID');
			    $table->string('engine_no')->default('')->comment('发动机型号');
			    $table->string('engine_company')->default('')->comment('发动机生产企业');
			    $table->string('engine_logo')->default('')->comment('发动机商标');
			    $table->string('engine_ml')->default('')->comment('发动机排量');
			    $table->string('engine_kw')->default('')->comment('发动机功率');
			});
			echo "init table data_engine!\r\n";
		}

		// 参数信息表
		if(!$Schema->hasTable('data_info'))
		{
			$Schema->create('data_info', function (Blueprint $table) {
			    $table->increments('id');
			    $table->integer('ino_jml_id')->nullable()->comment('品牌车型ID');
			    $table->string('ino_label')->default('')->comment('参数ID');
			    $table->string('ino_text')->default('')->comment('参数名称');
			    $table->text('ino_value')->nullable()->comment('参数值');
			});
			echo "init table data_info!\r\n";
		}

		// 汽车品牌表
		if(!$Schema->hasTable('data_make'))
		{
			$Schema->create('data_make', function (Blueprint $table) {
			    $table->increments('jme_id');
			    $table->char('jme_prefix',2)->nullable()->comment('厂商品牌首字母');
			    $table->string('jme_make')->default('')->comment('厂商品牌');
			    $table->string('jme_tmp')->default('')->comment('厂商品牌简称');
			    $table->text('jme_en')->nullable()->comment('厂商品牌拼音');
			});
			echo "init table data_make!\r\n";
		}

		// 汽车品牌车型表
		if(!$Schema->hasTable('data_model'))
		{
			$Schema->create('data_model', function (Blueprint $table) {
			    $table->increments('jml_id');
			    $table->tinyInteger('jml_action')->default(0)->comment('1:不上市, 2:不关注');
			    $table->integer('jml_jme_id')->nullable()->comment('生产商家品牌ID');
			    $table->integer('jml_car_id')->nullable()->comment('汽车类型');
			    $table->string('jml_make')->nullable()->comment('生产商家');
			    $table->string('jml_model')->nullable()->comment('车型');
			    $table->string('jml_name')->nullable()->comment('汽车类型');
			    $table->string('jml_company')->nullable()->comment('企业名称');
			    $table->text('jml_plist')->nullable()->comment('批次列表');
			    $table->integer('jml_max_p')->nullable()->comment('最新批次');
			    $table->string('jml_max_t')->default('')->comment('最新批次发布时间');
			    $table->integer('jml_min_p')->nullable()->comment('最小批次');
				$table->string('jml_min_t')->nullable()->comment('最小批次发布时间');
				$table->string('jml_cartons')->nullable()->comment('货箱');
				$table->string('jml_axis')->nullable()->comment('轴');
				$table->string('jml_exterior')->nullable()->comment('外观');
				$table->string('jml_weight')->nullable()->comment('重量');
				$table->string('jml_wheel')->nullable()->comment('轴距');
				$table->string('jml_fuel')->nullable()->comment('燃油');
				$table->string('jml_people')->nullable()->comment('乘坐人数');
			});
			echo "init table data_make!\r\n";
		}


		// 汽车识别码跟品牌车型关联表
		if(!$Schema->hasTable('data_model_vins'))
		{
			$Schema->create('data_model_vins', function (Blueprint $table) {
			    $table->increments('dmv_id');
			    $table->integer('dmv_jml_id')->nullable()->comment('品牌车型ID');
			    $table->integer('dmv_vin_id')->nullable()->comment('vin识别代号ID');
			});
			echo "init table data_model_vins!\r\n";
		}


		// 汽车批次信息表
		if(!$Schema->hasTable('data_post'))
		{
			$Schema->create('data_post', function (Blueprint $table) {
			    $table->increments('dpt_id');
			    $table->integer('dpt_num')->nullable()->comment('批次号');
			    $table->string('dpt_time')->nullable()->comment('批次时间');
			});
			$temp = require('datapost.php'); 
			// 未找到合适的数据源，暂为手动
			$newTemp = array();
			foreach ($temp as $k => $v)
			{
				$newTemp[] = ['dpt_num' =>$k, 'dpt_time' =>$v ];
			}
			// 入库所有参数
			Capsule::connection('model_jdcswww')->table('data_post')->insert($newTemp);
			echo "init table data_post!\r\n";
		}
		
		// 汽车识别码
		if(!$Schema->hasTable('data_vins'))
		{
			$Schema->create('data_vins', function (Blueprint $table) {
			    $table->increments('vin_id');
			    $table->string('vin_3bit')->nullable();
			    $table->string('vin_8bit')->nullable();
			});
			echo "init table data_vins!\r\n";
		}

		// 汽车外形表
		if(!$Schema->hasTable('data_weight'))
		{
			$Schema->create('data_weight', function (Blueprint $table) {
			    $table->increments('dwt_id');
			    $table->integer('dwt_jml_id')->nullable()->comment('品牌车型ID');
			    $table->string('dwt_weight')->nullable()->comment('长宽高');
			    $table->integer('dwt_long')->nullable()->comment('车长');
			    $table->integer('dwt_width')->nullable()->comment('车宽');
			    $table->integer('dwt_high')->nullable()->comment('车高');
			});
			echo "init table data_weight!\r\n";
		}

		// 汽车外形表
		if(!$Schema->hasTable('data_wheel'))
		{
			$Schema->create('data_wheel', function (Blueprint $table) {
			    $table->increments('dwl_id');
			    $table->integer('dwl_jml_id')->nullable()->comment('品牌车型ID');
			    $table->integer('dwl_lab_id')->nullable()->comment('参数名称ID');
			    $table->string('dwl_wheel')->nullable()->comment('轴距');
			});
			echo "init table data_wheel!\r\n";
		}


		// 汽车参数表
		if(!$Schema->hasTable('data_label'))
		{
			$Schema->create('data_label', function (Blueprint $table) {
			    $table->increments('lbl_id');
			    $table->string('lbl_label')->comment('参数名称');
			    $table->tinyInteger('lbl_order')->default(1)->comment('排序');
			    $table->string('lbl_string')->comment('首字母');
			    $table->string('lbl_en')->comment('英文名');
			});
			// 入库初始数据
			$temp = array( 
				'ggxh' => '公告型号',
				'ggpc' => '公告批次',
				'pp' => '品牌',
				'lx' => '类型', 
				'edzl' => '额定质量',
				'zzl' => '总质量',
				'zbzl' => '整备质量',
				'rlzl' => '燃料种类', 
				'pfyjbz' => '排放依据标准' ,
				'zs' => '轴数',
				'zj' => '轴距',
				'zh' => '轴荷',
				'thps' => '弹簧片数', 
				'lts' => '轮胎数', 
				'ltgg' => '轮胎规格' ,
				'jjlqj' => '接近离去角',
				'qxhx' => '前悬后悬' ,
				'qlj' => '前轮距', 
				'hlj' => '后轮距' ,
				'sbdh' => '识别代号', 
				'zcz' => '整车长', 
				'zck' => '整车宽',
				'zcg' => '整车高',
				'hxz' => '货厢长', 
				'hxk' => '货厢宽',
				'hxg' => '货厢高', 
				'zgcs' => '最高车速', 
				'edzk' => '额定载客', 
				'jsszcrs' => '驾驶室准乘人数', 
				'zxxs' => '转向形式', 
				'ztgczzl' => '准拖挂车总质量',
				'zzllyxs' => '载质量利用系数', 
				'bgcazzdczzl' => '半挂车鞍座最大承载质量', 
				'qymc' => '企业名称',
				'qydz' => '企业地址', 
				'dhhm' => '电话号码', 
				'czhm' => '传真号码' ,
				'yzbm' => '邮政编码',
				'dp1' => '底盘1',
				'dp2' => '底盘2',
				'dp3' => '底盘3',
				'dp4' => '底盘4',
				'bz' => '备注'
			);
			$newTemp = array();
			foreach ($temp as $k => $v)
			{
				$newTemp[] = ['lbl_label' =>$v, 'lbl_string' =>substr($k,0,1) ,'lbl_en'=>$k];
			}
			// 入库所有参数
			Capsule::connection('model_jdcswww')->table('data_label')->insert($newTemp);
		}
		echo "init table data_label!\r\n";
	}

	// 数据整理
	public static function cleandata()
	{	
		// chunk分块处理每100条数据进行清洗
		Capsule::table('raw_data')->where('status','wait')->orderBy('id')->chunk(1000,function($datas){
			$pinyin = new Pinyin();
			// 最终入库的数据库连接对象
			$finalDatabase = Capsule::connection('model_jdcswww');
			// 日志操作类
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'fivestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	if(empty($data->ggxh) || empty($data->pp))
		    	{
		    		// 车型、品牌两者必须同时不能为空否则认定为在脏数据
		    		continue;
		    	}
		    	// 处理品牌
		    	if(!strpos($data->pp, '牌'))
		    	{
		    		$datapp = $data->pp.'牌';
		    	}
		    	else
		    	{
		    		$datapp = $data->pp;
		    	}
	            // 校验品牌车型是否已经存在
		    	$data_model_data = $finalDatabase->table('data_model')
			    	->where([
					    ['jml_make', '=', $datapp],
	                    ['jml_model', '=', $data->ggxh]
					])->get()->isEmpty();

	            if($data_model_data)
	            {
			    	// 车型库
			    	if(!empty($data->lx))
			    	{
				    	$noHascar = $finalDatabase->table('data_cars')->where('car_text', $data->lx)->get()->isEmpty();
				    	if($noHascar)
				    	{
				    		// 如果不存在该车型
				    		$temp = [
				    			'car_text' => $data->lx
				    		];
				    		// 插入记录并获取ID
							$car_id = $finalDatabase->table('data_cars')->insertGetId($temp);
				    	}
				    	else
				    	{
				    		// 获取id
				    		$res = $finalDatabase->table('data_cars')->where('car_text', $data->lx)->first();
				    		$car_id = $res->car_id;
				    	}
			    	}
			    	// 品牌
			    	if(!empty($data->pp))
			    	{
				    	$data_pp = $finalDatabase->table('data_make')->where('jme_make', $datapp)->first();

				    	if(empty($data_pp))
				    	{
				    		// 如果不存在该车型
				    		$temp = [
				    			'jme_prefix' => substr($pinyin->abbr($datapp),0,1),
				    			'jme_make' => $datapp,
				    			'jme_tmp' => preg_replace('/牌$/', '', $datapp),
				    			'jme_en' => $pinyin->sentence(mb_substr($data->pp,0,1),true)
				    		];
				    		// 插入记录并获取ID
							$jme_id = $finalDatabase->table('data_make')->insertGetId($temp);
				    	}
				    	else
				    	{
				    		$jme_id = $data_pp->jme_id;
				    	}
			    	}
	            	// 获取批次发布时间
	            	$res =  $finalDatabase->table('data_post')->where('dpt_num',$data->ggpc)->first();
	            	if(!empty($res))
	            	{
	            		$pici_shijian = $res->dpt_time;
	            	}
	            	else
	            	{
	            		// 该日期表示批次发布时间未找到
	            		$pici_shijian = $data->ggpc;
	            	}
	            	// 记录外观
	            	if(!empty($data->zcz) && !empty($data->zck) && !empty($data->zcg))
	            	{
	            		$jml_exterior = $data->zcz .'x'.$data->zck.'x'.$data->zcg;
	            	}
	            	else
	            	{
	            		$jml_exterior = '';
	            	}
	            	// 记录货箱长宽高
	            	if(!empty($data->hxz) && !empty($data->hxk) && !empty($data->hxg))
	            	{
	            		$jml_cartons = $data->hxz .'x'.$data->hxk.'x'.$data->hxg;
	            	}
	            	else
	            	{
	            		$jml_cartons = '';
	            	}
	            	// 不存在该品牌车型
	            	$temp = [
	            		'jml_action' => 0,
	            		'jml_jme_id' => $jme_id,
	            		'jml_car_id' => $car_id,
	            		'jml_make' => $datapp,
	            		'jml_model' => $data->ggxh,
	            		'jml_name' => $data->lx,
	            		'jml_company' => $data->qymc,
	            		'jml_plist' => $data->ggpc,
	            		'jml_max_p' => $data->ggpc,
	            		'jml_max_t' => $pici_shijian,
	            		'jml_min_p' => $data->ggpc,
	            		'jml_min_t' => $pici_shijian,
	            		'jml_cartons' => $jml_cartons,
	            		'jml_axis' => $data->zs,
	            		'jml_exterior' => $jml_exterior,
	            		'jml_weight' => $data->zzl,
	            		'jml_wheel'=> $data->zj,
	            		'jml_fuel' => $data->rlzl,
	            		'jml_people'=>$data->jsszcrs
	            	];

	            	//插入记录并且获取品牌车型ID
	            	$jml_id = $finalDatabase->table('data_model')->insertGetId($temp);

			    	// 汽车长宽高
			    	if(!empty($data->zcz) && !empty($data->zcg) && !empty($data->zck))
			    	{
			    		$temp = [
			    			'dwt_jml_id' => $jml_id,
			    			'dwt_weight' => $data->zcz.'x'.$data->zck.'x'.$data->zcg,
			    			'dwt_long' => (int)$data->zcz,
			    			'dwt_width' => (int)$data->zck,
			    			'dwt_high' => (int)$data->zcg
			    		];
			    		$finalDatabase->table('data_weight')->insert($temp);
			    	}
			    	// 轴距
			    	if(!empty($data->zj))
			    	{
			    		$rez = $finalDatabase->table('data_label')->where('lbl_en','zj')->first();
			    		$zjArr = explode(',', $data->zj);
			    		foreach ($zjArr as $k => $v)
			    		{
				    		$temp = [
				    			'dwl_jml_id' => $jml_id,
				    			'dwl_lab_id' =>$rez->lbl_id,
				    			'dwl_wheel' => $v
				    		];
				    		$finalDatabase->table('data_wheel')->insert($temp);
			    		}
			    	}

			    	// 发动机
			    	if(!empty($data->fdjxh)&&!empty($data->fdjscqy)&&!empty($data->pl)&&!empty($data->gl))
			    	{
			    		$fdjxh = explode('<br>', $data->fdjxh);
			    		$fdjscqy = explode('<br>',$data->fdjscqy);
			    		// 处理发动机商标
			    		if(!empty($data->fdjsb))
			    		{
			    			$fdjsb = explode('<br>',$data->fdjsb);
			    		}
			    		else
			    		{
			    			$fdjsb =[0=>''];
			    		}

			    		$pl = explode('<br>', $data->pl);
			    		$gl = explode('<br>', $data->gl);

			    		// 以发动机型号数量为准，一个型号对应一个生产厂家一个功率一个排量
			    		$fdjxh = array_filter($fdjxh);

			    		foreach ($fdjxh as $k => $v)
			    		{
			    			// 型号与厂家可能存在多对一
			    			if(!empty($fdjscqy[$k]))
			    			{
			    				// 有时候一个型号一个厂家
			    				$gg = $fdjscqy[$k];
			    			}
			    			else
			    			{
			    				// 有时候几个型号一个厂家
			    				$gg = current($fdjscqy);
			    			}
			    			$temp = [
			    				'engine_jml_id' => $jml_id,
			    				'engine_no' => $v,
			    				'engine_company' => $gg,
			    				// 商标
			    				'engine_logo' => isset($fdjsb[$k])? $fdjsb[$k] : $fdjsb[0],
			    				// 排量
			    				'engine_ml' =>  isset($pl[$k]) ? $pl[$k] : $pl[0],
			    				// 功率
			    				'engine_kw' => isset($gl[$k]) ? $gl[$k] : $gl[0]
			    			];
			    			$finalDatabase->table('data_engine')->insert($temp);
			    		}
			    	}
			    	//data_info 汽车参数表,数组对象转数组
			    	$newData = self::object_to_array($data);
			    	foreach ($newData as $k => $v)
			    	{
			    		// 获取当前这个的data_label记录
			    		$res = $finalDatabase->table('data_label')->where('lbl_en',$k)->first();
			    		if(!empty($res))
			    		{
			    			// 过滤id还有发动机参数
			    			$temp = [
				    			'ino_jml_id' => $jml_id,
				    			'ino_label' => $res->lbl_id,
				    			'ino_text' => $res->lbl_label,
				    			'ino_value' => $v
				    		];
				    		$finalDatabase->table('data_info')->insert($temp);
			    		}
			    	}
			    	// 汽车识别代号直接当成一个完整的vin
			    	if(!empty($data->sbdh))
			    	{
			    		$sbdhArr = explode(',', $data->sbdh);
			    		foreach ($sbdhArr as $v)
			    		{
				    		$temp = [
				    			'vin_3bit' => substr($v,0,3),
				    			'vin_8bit' => substr($v,0,8)
				    		];
				    		$vin_id = $finalDatabase->table('data_vins')->insertGetId($temp);
				    		$temp = [
				    			'dmv_jml_id' => $jml_id,
				    			'dmv_vin_id' => $vin_id
				    		];
				    		$finalDatabase->table('data_model_vins')->insert($temp);
			    		}
			    	}		    	
	            }
	            else
	            {
	            	// 品牌车型已经存在,做更新操作
			    	$res = $finalDatabase->table('data_model')
				    	->where([
						    ['jml_make', '=', $datapp],
		                    ['jml_model', '=', $data->ggxh]
						])->first();

					// 品牌车型ID
				    $old_jml_id = $res->jml_id;
				    // 数据库最新批次
				    $old_jml_max_p = $res->jml_max_p;

				   	// 公告批次列表
				   	$old_jml_plist = $res->jml_plist;

				    // 如果公告批次更大就处理操作

				    if($data->ggpc>$old_jml_max_p)
				    {
				    	// 车型不更新

			    		// 品牌不更新

		            	// 获取批次发布时间
		            	$res =  $finalDatabase->table('data_post')->where('dpt_num',$data->ggpc)->first();
		            	if(!empty($res))
		            	{
		            		$pici_shijian = $res->dpt_time;
		            	}
		            	else
		            	{
		            		// 该时间用批次表示，说明该批次发布时间未找到
		            		$pici_shijian = $data->ggpc;
		            	}
		            	// 记录最新批次外观
		            	if(!empty($data->zcz) && !empty($data->zck) && !empty($data->zcg))
		            	{
		            		$jml_exterior = $data->zcz .'x'.$data->zck.'x'.$data->zcg;
		            	}
		            	else
		            	{
		            		$jml_exterior = '';
		            	}
		            	// 记录最新批次货箱长宽高
		            	if(!empty($data->hxz) && !empty($data->hxk) && !empty($data->hxg))
		            	{
		            		$jml_cartons = $data->hxz .'x'.$data->hxk.'x'.$data->hxg;
		            	}
		            	else
		            	{
		            		$jml_cartons = '';
		            	}
		            	// 拼接公告批次列表 2,5,9
		            	$temp = explode(',', $old_jml_plist);
		            	array_push($temp,$data->ggpc);
		            	// 批次数由大到小排序
		            	arsort($temp);
		            	$old_jml_plist = implode(',', $temp); 

		            	$temp = [
						    // 生产商家品牌ID
						    // jml_car_id
						    // 生产商家
						    // 车型好
						   	// 汽车类型
		            		// 企业名称
		            		'jml_company' => $data->qymc,
		            		// 公告批次列表
		            		'jml_plist' => $old_jml_plist,
		            		// 最新批次改变
		            		'jml_max_p' => $data->ggpc,
		            		// 最新批次发布时间更改
		            		'jml_max_t' => $pici_shijian,
		            		// 货箱长宽高
		            		'jml_cartons' => $jml_cartons,
		            		'jml_axis' => $data->zs,
		            		// 外观
		            		'jml_exterior' => $jml_exterior,
		            		'jml_weight' => $data->zzl,
		            		'jml_wheel'=> $data->zj,
		            		'jml_fuel' => $data->rlzl,
		            		'jml_people'=>$data->jsszcrs
		            	];
		            	//更新品牌车型记录表
		            	$finalDatabase->table('data_model')
				            ->where('jml_id', $old_jml_id)
				            ->update($temp);

				    	// 更新汽车长宽高
				    	if(!empty($data->zcz) && !empty($data->zcg) && !empty($data->zck))
				    	{
				    		$res = $finalDatabase->table('data_weight')->where('dwt_jml_id', $old_jml_id)->first();
				    		if(!empty($res))
				    		{
					    		$temp = [
					    			'dwt_weight' => $data->zcz.'x'.$data->zck.'x'.$data->zcg,
					    			'dwt_long' => (int)$data->zcz,
					    			'dwt_width' => (int)$data->zck,
					    			'dwt_high' => (int)$data->zcg
					    		];
					    		$finalDatabase->table('data_weight')
						            ->where('dwt_jml_id', $old_jml_id)
						            ->update($temp);
				    		}
				    		else
				    		{
				    			$temp = [
					    			'dwt_jml_id' => $old_jml_id,
					    			'dwt_weight' => $data->zcz.'x'.$data->zck.'x'.$data->zcg,
					    			'dwt_long' => (int)$data->zcz,
					    			'dwt_width' => (int)$data->zck,
					    			'dwt_high' => (int)$data->zcg
					    		];
					    		$finalDatabase->table('data_weight')->insert($temp);
				    		}
				    	}
				    	
				    	// 删除所有该品牌车型的轴距
				    	$finalDatabase->table('data_wheel')->where('dwl_jml_id', $old_jml_id)->delete();
				    	// 轴距
				    	if(!empty($data->zj))
				    	{
				    		$rez = $finalDatabase->table('data_label')->where('lbl_en','zj')->first();
				    		$zjArr = explode(',', $data->zj);
				    		foreach ($zjArr as $k => $v)
				    		{
					    		$temp = [
					    			'dwl_jml_id' => $old_jml_id,
					    			'dwl_lab_id' =>$rez->lbl_id,
					    			'dwl_wheel' => $v
					    		];
					    		$finalDatabase->table('data_wheel')->insert($temp);
				    		}
				    	}
						//批量更新同意品牌车型、同一种参数的汽车参数表
				    	$newData = self::object_to_array($data);
				    	foreach ($newData as $k => $v)
				    	{
				    		// 获取当前这个的data_label记录
				    		$res = $finalDatabase->table('data_label')->where('lbl_en',$k)->first();
				    		if(!empty($res))
				    		{
				    			// 过滤id还有发动机参数
				    			$temp = [
					    			'ino_value' => $v
					    		];
					    		$finalDatabase->table('data_info')
						            ->where([
									    ['ino_jml_id', '=', $old_jml_id],
					                    ['ino_label', '=',$res->lbl_id]
									])
						            ->update($temp);
				    		}
				    	}
				    	// 以最新批次vin为准，更新所有vin
				    	$res = $finalDatabase->table('data_model_vins')
				    			->where('dmv_jml_id',$old_jml_id)
				    			->get()->toArray();
				    	$ids = array();
				    	foreach ($res as $k => $v)
				    	{
				    		$ids[] = $v->dmv_vin_id;
				    	}
				    	$finalDatabase->table('data_vins')->where('vin_id', 'in', $ids)->delete();
				    	$res = $finalDatabase->table('data_model_vins')
				    			->where('dmv_jml_id',$old_jml_id)
				    			->delete();
				    	// 汽车识别代号直接当成一个完整的vin
				    	if(!empty($data->sbdh))
				    	{
				    		$sbdhArr = explode(',', $data->sbdh);
				    		foreach ($sbdhArr as $v)
				    		{
					    		$temp = [
					    			'vin_3bit' => substr($v,0,3),
					    			'vin_8bit' => substr($v,0,8)
					    		];
					    		$vin_id = $finalDatabase->table('data_vins')->insertGetId($temp);
					    		$temp = [
					    			'dmv_jml_id' => $old_jml_id,
					    			'dmv_vin_id' => $vin_id
					    		];
					    		$finalDatabase->table('data_model_vins')->insert($temp);
				    		}
				    	}
				    	// 删除所有原有发动机数据全部更新为最新批次的发动机数据
				    	$finalDatabase->table('data_engine')->where('engine_jml_id','=',$old_jml_id)->delete();
				    	// 当发动机型号有数据库之中不存在的型号时候就需要更新发动机型号
				    	if(!empty($data->fdjxh)&&!empty($data->fdjscqy)&&!empty($data->pl)&&!empty($data->gl))
				    	{
				    		$fdjxh = explode('<br>', $data->fdjxh);
				    		$fdjscqy = explode('<br>',$data->fdjscqy);
				    		// 处理发动机商标
				    		if(!empty($data->fdjsb))
				    		{
				    			$fdjsb = explode('<br>',$data->fdjsb);
				    		}
				    		else
				    		{
				    			$fdjsb =[0=>''];
				    		}

				    		$pl = explode('<br>', $data->pl);
				    		$gl = explode('<br>', $data->gl);

				    		// 以发动机型号数量为准，一个型号对应一个生产厂家一个功率一个排量
				    		$fdjxh = array_filter($fdjxh);

				    		foreach ($fdjxh as $k => $v)
				    		{
				    			// 型号与厂家可能存在多对一
				    			if(!empty($fdjscqy[$k]))
				    			{
				    				// 有时候一个型号一个厂家
				    				$gg = $fdjscqy[$k];
				    			}
				    			else
				    			{
				    				// 有时候几个型号一个厂家
				    				$gg = current($fdjscqy);
				    			}
				    			$temp = [
				    				'engine_jml_id' => $old_jml_id,
				    				'engine_no' => $v,
				    				'engine_company' => $gg,
				    				// 商标
				    				'engine_logo' => isset($fdjsb[$k])? $fdjsb[$k] : $fdjsb[0],
				    				// 排量
				    				'engine_ml' =>  isset($pl[$k]) ? $pl[$k] : $pl[0],
				    				// 功率
				    				'engine_kw' => isset($gl[$k]) ? $gl[$k] : $gl[0]
				    			];
				    			$finalDatabase->table('data_engine')->insert($temp);
				    		}
				    	}
				    }
				    else
				    {
				    	// 此时仅仅需要判定是否需要更新批次列表
				    	$old_jml_plist_arr = explode(',', $old_jml_plist);
					   	if(!in_array($data->ggpc, $old_jml_plist_arr))
					   	{
					   		array_push($old_jml_plist_arr, $data->ggpc);
		
					   		// 重新排序
					   		arsort($old_jml_plist_arr);
					   		// 去重复
					   		$old_jml_plist_arr = array_unique($old_jml_plist_arr);
					   		// 如果是最小的需要更新数据
					   		if($data->ggpc == end($old_jml_plist_arr))
					   		{
					   			// 获取批次发布时间
				            	$res =  $finalDatabase->table('data_post')->where('dpt_num',$data->ggpc)
				            			->first();
				            	if(!empty($res))
				            	{
				            		$pici_shijian = $res->dpt_time;
				            	}
				            	else
				            	{
				            		// 该日期表示批次发布时间未找到
				            		$pici_shijian = $data->ggpc;
				            	}
					   			$temp = [
					    			'jml_plist' => implode(',', $old_jml_plist_arr),
					    			'jml_min_p' => $data->ggpc,
					    			'jml_min_t' => $pici_shijian

					    		];
					   		}
					   		else
					   		{
						   		$temp = [
					    			'jml_plist' => implode(',', $old_jml_plist_arr),
					    		];
					   		}
					   		// 出现一个更老的批次，紧紧将批次列表更新
			            	$finalDatabase->table('data_model')
					            ->where('jml_id', $old_jml_id)
					            ->update($temp);
						}
				    }
	            }
	            // 更改原始数据是否status
	            Capsule::table('raw_data')->where('id',$data->id)->update(['status' =>'readed']);
	            echo $data->id." analyse completed! \r\n";
	            $LibFile->WriteData($logFile, 4, $data->id.'数据整理完毕！');
		    }
		});
	}


	/**
	 * 对象转数组
	 * @param object $obj 对象
	 * @return array
	 */
	public static function object_to_array($obj) {
	    $obj = (array)$obj;
	    foreach ($obj as $k => $v) {
	        if (gettype($v) == 'resource') {
	            return;
	        }
	        if (gettype($v) == 'object' || gettype($v) == 'array') {
	            $obj[$k] = (array)object_to_array($v);
	        }
	    }
	    return $obj;
	}

}
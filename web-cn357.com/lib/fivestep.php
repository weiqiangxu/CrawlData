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
		
		if(!$Schema->hasTable('brand'))
		{
			// 如果不存在品牌表就创建这个数据表
			$Schema->create('brand', function (Blueprint $table) {
			    $table->increments('ul_id');
			    $table->string('ul_url');
			    $table->string('ul_status');
			    $table->string('ul_filename');
			    $table->string('ul_filepath');
			});
		}
		echo "init table successful!\r\n";
	}

	// 数据清洗
	public static function cleandata()
	{	
		// chunk分块处理每100条数据进行清洗
		Capsule::table('raw_data')->orderBy('id')->chunk(1000,function($datas){
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
	            	if(!empty($data->hxz) && !empty($data->hxk) && !empty($data->hxg))
	            	{
	            		$jml_exterior = $data->zcz .'x'.$data->zck.'x'.$data->zcg;
	            	}
	            	else
	            	{
	            		$jml_exterior = '';
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
	            		'jml_cartons' => $data->hxz.'x'.$data->hxk.'x'.$data->hxg,
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
			    	// 汽车识别代号
			    	if(!empty($data->sbdh))
			    	{
			    		$temp = [
			    			'vin_3bit' => substr($data->sbdh,0,3),
			    			'vin_8bit' => substr($data->sbdh,0,8)
			    		];
			    		$vin_id = $finalDatabase->table('data_vins')->insertGetId($temp);
			    		$temp = [
			    			'dmv_jml_id' => $jml_id,
			    			'dmv_vin_id' => $vin_id
			    		];
			    		$finalDatabase->table('data_model_vins')->insert($temp);
			    	}
			    	// 轴距
			    	if(!empty($data->zj))
			    	{
			    		$temp = [
			    			'dwl_jml_id' => $jml_id,
			    			'dwl_lab_id' => 0,
			    			'dwl_wheel' => $data->zj
			    		];
			    		$finalDatabase->table('data_wheel')->insert($temp);
			    	}

			    	// 发动机
			    	if(!empty($data->fdjxh)&&!empty($data->fdjscqy)&&!empty($data->pl)&&!empty($data->gl))
			    	{
			    		$fdjxh = explode('<br>', $data->fdjxh);
			    		$fdjscqy = explode('<br>',$data->fdjscqy);
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

			    		$fdjxh = array_filter($fdjxh);

			    		foreach ($fdjxh as $k => $v)
			    		{
			    			if(!empty($fdjscqy[$k]))
			    			{
			    				$gg = $fdjscqy[$k];
			    			}
			    			else
			    			{
			    				$gg = current($fdjscqy);
			    			}
			    			$temp = [
			    				'engine_jml_id' => $jml_id,
			    				'engine_no' => $v,
			    				'engine_company' => $gg,
			    				'engine_logo' => isset($fdjsb[$k])? $fdjsb[$k] : $fdjsb[0],
			    				'engine_ml' =>  isset($pl[$k]) ? $pl[$k] : $pl[0],
			    				'engine_kw' => isset($gl[$k]) ? $gl[$k] : $gl[0]
			    			];
			    			$finalDatabase->table('data_engine')->insert($temp);
			    		}
			    	}
			    	die;
	            }
	            else
	            {
	            	// 品牌车型已经存在
	            }
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
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
  * @author xu
  * @copyright 2018/03/28
  */
class fourstep{


	// 原始数据

	/* stdClass Object ( 
		[model] => Engine/Fuel 
		[source] => http://www.toyodiy.com/parts/q?vin=ASA44L-ANTGKC 
		[id] => 1 
		[car_id] => 1 
		[url] => http://www.toyodiy.com/parts/p_G_201308_TOYOTA_RAV4_ASA44L-ANTGKC_0901.html 
		[part_type] => STANDARD TOOL 
		[part_type_num] => 09-01 
		[part_type_page] => 1 
		[part_detail_num] => 09111-0R010 
		[part_detail_des] => 
		[part_detail_sum] => 1 
		[part_detail_name] => JACK ASSY 
		[part_detail_prefix] => ('1308- ) 
		[status] => wait 
	)
	*/

	// 迁移数据
	public static function move()
	{
		$pinyin = new Pinyin();
		$empty = Capsule::table('part_detail')->where('status','wait')->get()->isEmpty();
	    // 循环
		while(!$empty) {
			$datas = Capsule::table('part_detail')
	            ->leftJoin('car', 'part_detail.car_id', '=', 'car.id')
	            ->select('car.model','car.source', 'part_detail.*')
	            ->where('part_detail.status','wait')
	            ->orderBy('part_detail.id','asc')
	            ->limit(1000)->get();

	        foreach ($datas as $data) {
	        	// 处理描述
	        	if(empty($data->part_detail_des)) $data->part_detail_des='';
	        	$data->part_detail_des = str_replace($data->part_detail_des, '', $data->part_detail_prefix).' '.$data->part_detail_des;
	        	// 处理vin
	        	$vin = str_replace('http://www.toyodiy.com/parts/q?vin=', '', $data->source);

	        	// 1、品牌表 
	        	$data_etks = ['etk_id' => 8,'etk_name'=>'丰田','etk_post'=>'','etk_pinyin'=>'toyota','etk_order'=>5];

	        	// 2、vin表
	        	$data_vin = Capsule::connection('yp_realoem')->table('data_vins')->where([['vin_name',$vin],['vin_etk_id',$data_etks['etk_id']]])->first();
			    if(empty($data_vin)){
			    	$temp = ['vin_etk_id'=>$data_etks['etk_id'],'vin_name'=>$vin,'vin_read'=>1];
			    	$data_vin_id = Capsule::connection('yp_realoem')->table('data_vins')->insertGetId($temp);
			    }else{
			    	$data_vin_id = $data_vin->vin_id;
			    }

	        	// 3、主组
				$data_partgrp1 = Capsule::connection('yp_realoem')->table('data_partgrp1')->where([['grp1_name',$data->model]])->first();
			    if(empty($data_partgrp1)){
			    	$temp = ['grp1_name'=>$data->model];
			    	$data_partgrp1_id = Capsule::connection('yp_realoem')->table('data_partgrp1')->insertGetId($temp);
			    }else{
			    	$data_partgrp1_id = $data_partgrp1->grp1_id;
			    }

	        	// 4、子组
			    $data_partgrp2 = Capsule::connection('yp_realoem')->table('data_partgrp2')->where([['grp2_code',$data->part_type_num],['grp2_name',$data->part_type]])->first();
			    if(empty($data_partgrp2)){
			    	$temp = ['grp2_code'=>$data->part_type_num,'grp2_name'=>$data->part_type];
			    	$data_partgrp2_id = Capsule::connection('yp_realoem')->table('data_partgrp2')->insertGetId($temp);
			    }else{
			    	$data_partgrp2_id = $data_partgrp2->grp2_id;
			    }

	        	// 5、关键字表 => 号码名称
	        	$data_keyword = Capsule::connection('yp_realoem')->table('data_keyword')->where([['key_keyword',trim($data->part_detail_name)]])->first();
			    if(empty($data_keyword)){
			    	$data_keyword_id = Capsule::connection('yp_realoem')->table('data_keyword')->insertGetId(['key_keyword'=>trim($data->part_detail_name)]);
			    }else{
			    	$data_keyword_id = $data_keyword->key_id;
			    }

			    // 6、产品表
			    $pro_pos = $data_etks['etk_pinyin'].','.strtolower($pinyin->permalink($data->part_detail_name,'_')).','.strtolower(str_replace('-','',$data->part_detail_num));
			    $temp = array(
			    	'pro_yp_part' => 0,
					'pro_etk_id' => $data_etks['etk_id'],
					'pro_realoem' => $data->part_detail_num, 
					'pro_name' => $data->part_detail_name,
					// data_etks 表 etk_pinyin字段 + 号码名称拼音（overtrue/pinyin）小写下划线 + 号码的去格式小写
					'pro_post' => $pro_pos,
					'pro_seo' => 1,
					'pro_yp_name' => ''
			    );
			    $data_product_pro_id = Capsule::connection('yp_realoem')->table('data_product')->insertGetId($temp);


			    // 7、产品与关键词表
			    $data_product_keyword = Capsule::connection('yp_realoem')->table('data_product_keyword')
			    		->where([['pkw_pro_id',$data_product_pro_id],['pkw_key_id',$data_keyword_id]])->first();
			    if(empty($data_product_keyword)){
			    	// 新增
			    	Capsule::connection('yp_realoem')->table('data_product_keyword')->insert(['pkw_pro_id'=>$data_product_pro_id,'pkw_key_id'=>$data_keyword_id]);
			    } 

			    // 8、产品搜索表
			    $temp = array(
					'src_pro_id' => $data_product_pro_id,
					'src_etk_id' => $data_etks['etk_id'],
					'src_main' => 1,
					'src_realoem' => $data->part_detail_num,
					'src_format' => strtolower(str_replace('-','',$data->part_detail_num)),
			    ); 
			    Capsule::connection('yp_realoem')->table('data_product_search')->insert($temp);

			    // 9、备注表
			    $data_text = Capsule::connection('yp_realoem')->table('data_text')->where([['txt_text',$data->part_detail_des]])->first();
			    if(empty($data_text)){
			    	$data_text_id = Capsule::connection('yp_realoem')->table('data_text')->insertGetId(['txt_text'=>$data->part_detail_des]);
			    }else{
			    	$data_text_id = $data_text->txt_id;
			    }

			    // 10、产品VIN表
			    $temp = array('ppv_pro_id'=>$data_product_pro_id,'ppv_vin_id'=>$data_vin_id);
			    Capsule::connection('yp_realoem')->table('data_product_vins')->insert($temp);

			    // 11、主主直主关联表
			    $temp = array(
					'pmg_parent' => '',
					'pmg_childern' => '',
					'pmg_grp1_id' => $data_partgrp1_id,
					'pmg_grp2_id' => $data_partgrp2_id,
					'pmg_order' => 999,
			    );
			    $pmg_id = Capsule::connection('yp_realoem')->table('data_partgrp')->insertGetId($temp);

			    // 12、总表
			    // 生成主键
			    $res = Capsule::connection('yp_realoem')->table('data_product_partgrp_8')->orderBy('ppm_id','desc')->first();
			    if(empty($res)){
			    	$ppm_id = 8268;
			    }else{
			    	if($res->ppm_id < 8267){ $ppm_id = 8268; }else{ $ppm_id = $res->ppm_id+1; }
			    }

			    $temp = array(
			    	'ppm_id' => $ppm_id,
					'ppm_pro_id' => $data_product_pro_id,
					'ppm_vin_id' => $data_vin_id,
					// 组级
					'ppm_pmg_id' => $pmg_id,
					// 图片
					'ppm_ppg_id' => 0,
					// 位置
					'ppm_pos_id' =>'',
					// 描述
					'ppm_txt_id' => $data_text_id,
					'ppm_order' => 999
			    );
			    Capsule::connection('yp_realoem')->table('data_product_partgrp_8')->insert($temp);
			    // 标记已读
				Capsule::table('part_detail')->where('id', $data->id)->update(['status' =>'readed']);
				echo 'part_detail '.$data->id.' move completed!'.PHP_EOL;
	        }
			$empty = Capsule::table('part_detail')->where('status','wait')->get()->isEmpty();
		}	
	}
}
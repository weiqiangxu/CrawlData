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
		// 1、品牌表 
    	$data_etks = ['etk_id' => 8,'etk_name'=>'丰田','etk_post'=>'','etk_pinyin'=>'toyota','etk_order'=>5];
		// 2、data_vins
		$vin_list1 = Capsule::connection('vin_list')->table('vin_list')->select('mec_code')->distinct()->get();
		$no_exist_vin = array();
		foreach ($vin_list1 as $v) { $no_exist_vin[] = $v->mec_code; }
		$vin_list2 = Capsule::connection('yp_realoem')->table('data_vins')->select('vin_name')->get();
		$exist_vin = array();
		foreach ($vin_list2 as $v) { $exist_vin[] = $v->vin_name; }
		$res = array_diff($no_exist_vin, $exist_vin);
		$temp = array();
		foreach ($res as $k => $v) { $temp[] = ['vin_id'=>8268+$k+1,'vin_etk_id'=>8,'vin_name'=>$v,'vin_read'=>1]; }
		if(!empty($res)){ Capsule::connection('yp_realoem')->table('data_vins')->insert($temp); }
		$res = Capsule::connection('yp_realoem')->table('data_vins')->select('vin_id','vin_name')->get();
		$data_vins = array();
		foreach ($res as $key => $value) { $data_vins[$value->vin_name] = $value->vin_id; }
		echo 'all vin insert'.PHP_EOL;

		// 3、主组
		$res1 = Capsule::table('car')->select('model')->distinct()->get();
		$no_exist = array();
		foreach ($res1 as $v) { $no_exist[] = $v->model; }
		$res2 = Capsule::connection('yp_realoem')->table('data_partgrp1')->select('grp1_name')->get();
		$exist = array();
		foreach ($res2 as $v) { $exist[] = $v->grp1_name; }
		$res = array_diff($no_exist, $exist);
		$temp = array();
		foreach ($res as $k => $v) { $temp[] = ['grp1_name'=>$v]; }
		if(!empty($res)){ Capsule::connection('yp_realoem')->table('data_partgrp1')->insert($temp); }
		$res = Capsule::connection('yp_realoem')->table('data_partgrp1')->select('grp1_id','grp1_name')->get();
		$data_partgrp1 = array();
		foreach ($res as $key => $value) { $data_partgrp1[md5($value->grp1_name)] = $value->grp1_id; }
		echo 'all data_partgrp1 insert'.PHP_EOL;

    	// 4、子组
		$res1 = Capsule::table('part_detail')->select('part_type','part_type_num')->distinct()->get();
		$no_exist = array();
		foreach ($res1 as $v) { $no_exist[] = ['grp2_name'=>$v->part_type,'grp2_code'=>$v->part_type_num];}
		$res2 = Capsule::connection('yp_realoem')->table('data_partgrp2')->select('grp2_code','grp2_name')->get();
		$exist = array();
		foreach ($res2 as $v) { $exist[] = ['grp2_name'=>$v->grp2_name,'grp2_code'=>$v->grp2_code];}
		$res = array_filter($no_exist,function($v)use($exist){ if(in_array($v, $exist)) return false;else return true;});
		if(!empty($res)) Capsule::connection('yp_realoem')->table('data_partgrp2')->insert($res);
		$res = Capsule::connection('yp_realoem')->table('data_partgrp2')->select('grp2_id','grp2_code','grp2_name')->get();
		$data_partgrp2 = array();
		foreach ($res as $k => $v) {
			$data_partgrp2[md5(sprintf('%s=%s', $v->grp2_code, $v->grp2_name))] = $v->grp2_id;
		}
		echo 'all data_partgrp2 insert'.PHP_EOL;

		// 5、备注表+分页实现
		$perpage = 10000;
		$page_count = ceil(Capsule::table('part_detail')->count()/$perpage);
		for ($i = 0; $i < $page_count; $i++) 
		{
			$res = Capsule::table('part_detail')->select('part_detail_des','part_detail_prefix')->whereBetween('id',[$i*$perpage+1, ($i+1)*$perpage])->distinct()->get();
			$no_exist = array();
			foreach ($res as $v) { $no_exist[] = str_replace($v->part_detail_des,'',$v->part_detail_prefix).' '.$v->part_detail_des;}
			$res = Capsule::connection('yp_realoem')->table('data_text')->select('txt_text')->get();
			$exist = array();
			foreach ($res as $v) { $exist[] = $v->txt_text; }
			$res = array_diff($no_exist, $exist);
			$temp = array();
			foreach ($res as $k => $v) { $temp[] = ['txt_text'=>$v]; }
			if(!empty($res)){ Capsule::connection('yp_realoem')->table('data_text')->insert($temp);}
			echo 'page '.$i.PHP_EOL;
		}
		$res = Capsule::connection('yp_realoem')->table('data_text')->select('txt_id','txt_text')->get();
		$data_text = array();
		foreach ($res as $k => $v) {$data_text[md5($v->txt_text)] = $v->txt_id; }
		echo 'all data_text insert'.PHP_EOL;


    	// 6、关键字表 => 号码的名称
		$res = Capsule::table('part_detail')->select('part_detail_name')->distinct()->get();
		$no_exist = array();
		foreach ($res as $v) { $no_exist[] = $v->part_detail_name; }
		$res = Capsule::connection('yp_realoem')->table('data_keyword')->select('key_keyword')->get();
		$exist = array();
		foreach ($res as $v) { $exist[] = $v->key_keyword; }
		$res = array_diff($no_exist, $exist);
		$temp = array();
		foreach ($res as $k => $v) { $temp[] = ['key_keyword'=>$v]; }
		if(!empty($res)){ Capsule::connection('yp_realoem')->table('data_keyword')->insert($temp); }
		$res = Capsule::connection('yp_realoem')->table('data_keyword')->select('key_id','key_keyword')->get();
		$data_keyword = array();
		foreach ($res as $k => $v) { $data_keyword[$v->key_keyword] = $v->key_id; }
		echo 'all data_keyword insert'.PHP_EOL;

		// 唯一标志符号
		$res = Capsule::connection('yp_realoem')->table('data_product')->select('pro_realoem','pro_name')->get();
		$all_realoem = array();
		foreach ($res as $k => $v) {
			$all_realoem[] = sprintf("%s=%s",$v->pro_realoem,$v->pro_name);
		}

		echo 'start insert pro'.PHP_EOL;


		$pinyin = new Pinyin();
		$empty = Capsule::table('part_detail')->where('status','wait')->get()->isEmpty();
	    // 循环
		while(!$empty) {
			$datas = Capsule::table('part_detail')->leftJoin('car', 'part_detail.car_id', '=', 'car.id')
		            ->select('car.model','car.source', 'part_detail.id','part_detail.part_type','part_detail.part_type_num','part_detail.part_detail_num','part_detail.part_detail_des','part_detail.part_detail_name','part_detail.part_detail_prefix')
		            ->where('part_detail.status','wait')
		            ->orderBy('part_detail.id','asc')
		            ->limit(1000)->get();
		    // 1、主组直组关联表
		    $insert_for_data_partgrp = array();
		    // 2、产品表
		    $insert_for_data_product = array();
		    // 3、产品关键词
		    $insert_for_data_product_keyword = array();
		    // 4、产品vin表
		    $insert_for_data_product_vin = array();
		    // 5、产品搜索表
		    $insert_for_data_product_search = array();
		    // 6、主表
		    $insert_for_data_product_partgrp8 = array();
		    // 获取产品最大ID
		    $res = Capsule::connection('yp_realoem')->table('data_product')->select('pro_id')->orderBy('pro_id','desc')->first();
		    $pro_id = $res->pro_id+1;
		    // 获取主组直组关联表最大ID
		    $res = Capsule::connection('yp_realoem')->table('data_partgrp')->select('pmg_id')->orderBy('pmg_id','desc')->first();
		    $pmg_id = $res->pmg_id+1;


		    $all_data_id = array();
	        // 循环数据入库
	        foreach ($datas as $key => $data) {
	        	// 校验号码唯一性
	        	$is_carry_on = sprintf("%s=%s",$data->part_detail_num,$data->part_detail_name);
	        	if(in_array($is_carry_on, $all_realoem)){
	        		continue;
	        	}else{
	        		$all_realoem[] = $is_carry_on;
	        	}
	        	// 描述
	        	if(empty($data->part_detail_des)) $data->part_detail_des='';
	        	$data->part_detail_des = str_replace($data->part_detail_des, '', $data->part_detail_prefix).' '.$data->part_detail_des;
	        	// vin
	        	$vin_text = str_replace('http://www.toyodiy.com/parts/q?vin=', '', $data->source);
	        	// 1、主组直组关联表
	        	$insert_for_data_partgrp[] = [
	        		'pmg_id' => $pmg_id,
	        		'pmg_parent' => '',
	        		'pmg_childern' => '',
	        		'pmg_grp1_id' => $data_partgrp1[md5($data->model)],
	        		'pmg_grp2_id' => $data_partgrp2[md5(sprintf('%s=%s',$data->part_type_num,$data->part_type))],
	        		'pmg_order' => 999
	        	];
			    // 2、产品表
			    $pro_pos = $data_etks['etk_pinyin'].','.strtolower($pinyin->permalink($data->part_detail_name,'_')).','.strtolower(str_replace('-','',$data->part_detail_num));
			    $temp = array(
			    	'pro_id' => $pro_id,
			    	'pro_yp_part' => 0,
					'pro_etk_id' => $data_etks['etk_id'],
					'pro_realoem' => $data->part_detail_num, 
					'pro_name' => $data->part_detail_name,
					'pro_post' => $pro_pos,
					'pro_seo' => 1,
					'pro_yp_name' => ''
			    );
			   $insert_for_data_product[] = $temp;
			    // 3、产品与关键词表
			   $insert_for_data_product_keyword[] = ['pkw_pro_id'=>$pro_id,'pkw_key_id'=>$data_keyword[$data->part_detail_name]];
			    // 4、产品搜索表
			    $temp = array(
					'src_pro_id' => $pro_id,
					'src_etk_id' => $data_etks['etk_id'],
					'src_main' => 1,
					'src_realoem' => $data->part_detail_num,
					'src_format' => strtolower(str_replace('-','',$data->part_detail_num)),
			    ); 
			    $insert_for_data_product_search[] = $temp;
			    // 5、产品VIN表
			    $insert_for_data_product_vin[] = ['ppv_pro_id'=>$pro_id,'ppv_vin_id'=>$data_vins[$vin_text]];

			    // 6、总表
			    $temp = array(
					'ppm_pro_id' => $pro_id,
					'ppm_vin_id' => $data_vins[$vin_text],
					'ppm_pmg_id' => $pmg_id,
					'ppm_ppg_id' => 0,
					'ppm_pos_id' =>'',
					'ppm_txt_id' => $data_text[md5(str_replace($data->part_detail_des,'',$data->part_detail_prefix).' '.$data->part_detail_des)],
					'ppm_order' => 999
			    );
			    $insert_for_data_product_partgrp8[] = $temp;

			    $pmg_id++;
			    $pro_id++;
			    $all_data_id[] = $data->id;
			    echo $data->id.PHP_EOL;
	        }
	        // 入库
		    // 1、主组直组关联表
		    Capsule::connection('yp_realoem')->table('data_partgrp')->insert($insert_for_data_partgrp);
		    // 2、产品表
		    Capsule::connection('yp_realoem')->table('data_product')->insert($insert_for_data_product);
		    // 3、产品关键词
		    Capsule::connection('yp_realoem')->table('data_product_keyword')->insert($data_product_keyword);
		    // 4、产品vin表
		    Capsule::connection('yp_realoem')->table('data_product_vin')->insert($insert_for_data_product_vin);
		    // 5、产品搜索表
		    Capsule::connection('yp_realoem')->table('data_product_search')->insert($insert_for_data_product_search);
		    // 6、主表
		    Capsule::connection('yp_realoem')->table('data_product_partgrp8')->insert($insert_for_data_product_partgrp8);

		    // 标记已读
		    if(!empty($all_data_id)) Capsule::table('part_detail')->where('id','in',implode(',',$all_data_id))->update(['status' =>'readed']);
			echo 'part_detail 1000 move completed!'.PHP_EOL;

			$empty = Capsule::table('part_detail')->where('status','wait')->get()->isEmpty();
		}	
	}
}
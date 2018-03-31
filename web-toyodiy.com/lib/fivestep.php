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
class fivestep{

	// 迁移数据
	public static function move()
	{
		// 1、品牌表 
    	$data_etks = ['etk_id' => 8,'etk_name'=>'丰田','etk_post'=>'','etk_pinyin'=>'toyota','etk_order'=>5];
		// 2、data_vins
		$vin_list1 = Capsule::table('car')->select('source')->distinct()->get();
		$no_exist = array();
		foreach ($vin_list1 as $v) { 
			$vin_text = str_replace('http://www.toyodiy.com/parts/q?vin=','', $v->source);
			$no_exist[] = $vin_text; 
		}
		$vin_list2 = Capsule::connection('yp_realoem')->table('data_vins')->select('vin_name')->get();
		$exist = array();
		foreach ($vin_list2 as $v) { $exist[] = $v->vin_name; }
		$res = array_diff($no_exist, $exist);
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

		// 3、主组直组关联表
		$perpage = 10000;
		$page_count = ceil(Capsule::table('part_detail')->count()/$perpage);
		for ($i = 0; $i < $page_count; $i++) 
		{
			$res = Capsule::table('part_detail')->leftJoin('car','part_detail.car_id','=','car.id')
	            ->select('car.model','part_detail.part_type','part_detail.part_type_num')->whereBetween('part_detail.id',[$i*$perpage+1, ($i+1)*$perpage])->get();
			$no_exist = array();
			foreach ($res as $v) {
				$no_exist[] = array(
					'pmg_parent' => '',
					'pmg_childern' => '',
					'pmg_grp1_id' => $data_partgrp1[md5($v->model)],
		        	'pmg_grp2_id' => $data_partgrp2[md5(sprintf('%s=%s',$v->part_type_num,$v->part_type))],
		        	'pmg_order' => 999
				); 
			}
			$res = Capsule::connection('yp_realoem')->table('data_partgrp')->select('pmg_grp1_id','pmg_grp2_id')->get();
			$exist = array();
			foreach ($res as $v) { 
				$exist[] = array(
					'pmg_parent' => '',
	        		'pmg_childern' => '',
					'pmg_grp1_id' => $v->pmg_grp1_id,
		        	'pmg_grp2_id' => $v->pmg_grp2_id,
		        	'pmg_order' => 999
				); 
			}
			$temp = array_filter($no_exist,function($v)use($exist){ if(in_array($v, $exist)) return false;else return true;});
			if(!empty($temp)) Capsule::connection('yp_realoem')->table('data_partgrp')->insert($temp);
			echo 'data_partgrp page '.$i.PHP_EOL;
		}
		$res = Capsule::connection('yp_realoem')->table('data_partgrp')->select('pmg_id','pmg_grp1_id','pmg_grp2_id')->get();
		$data_partgrp = array();
		foreach ($res as $k => $v) {$data_partgrp[sprintf("%s=%s",$v->pmg_grp1_id,$v->pmg_grp2_id)] = $v->pmg_id; }
		echo 'all data_text insert'.PHP_EOL;



		// 5、备注表+分页实现
		$perpage = 10000;
		$page_count = ceil(Capsule::table('part_detail')->count()/$perpage);
		for ($i = 0; $i < $page_count; $i++) 
		{
			$res = Capsule::table('part_detail')->select('part_detail_des','part_detail_prefix')->whereBetween('id',[$i*$perpage+1, ($i+1)*$perpage])->get();
			$no_exist = array();
			foreach ($res as $v) {
				if(empty($v->part_detail_des) || empty(str_replace($v->part_detail_des,'',$v->part_detail_prefix)))
				{
					$this_txt =  $v->part_detail_prefix;
				}
				else
				{
		        	$this_txt = str_replace($v->part_detail_des,'',$v->part_detail_prefix).' '.$v->part_detail_des;
				}
				$no_exist[] = $this_txt;
			}
			$res = Capsule::connection('yp_realoem')->table('data_text')->select('txt_text')->get();
			$exist = array();
			foreach ($res as $v) { $exist[] = $v->txt_text;}
			$res = array_diff($no_exist, $exist);
			$temp = array();
			foreach ($res as $v) { $temp[] = ['txt_text'=>$v]; }
			if(!empty($res)){ Capsule::connection('yp_realoem')->table('data_text')->insert($temp);}
			echo 'page '.$i.PHP_EOL;
		}
		$res = Capsule::connection('yp_realoem')->table('data_text')->select('txt_id','txt_text')->get();
		$data_text = array();
		foreach ($res as $k => $v) {$data_text[$v->txt_text] = $v->txt_id; }
		echo 'all data_text insert'.PHP_EOL;


    	// 6、关键字表 => 号码的名称
		$res = Capsule::table('part_detail')->select('part_detail_name')->get();
		$no_exist = array();
		foreach ($res as $v) { $no_exist[] = $v->part_detail_name; }
		$res = Capsule::connection('yp_realoem')->table('data_keyword')->select('key_keyword')->get();
		$exist = array();
		foreach ($res as $v) { $exist[] = $v->key_keyword; }
		$res = array_diff($no_exist, $exist);
		$temp = array();
		foreach ($res as $k => $v) { $temp[] = ['key_keyword'=>$v]; }
		if(!empty($temp)){ Capsule::connection('yp_realoem')->table('data_keyword')->insert($temp); }
		$res = Capsule::connection('yp_realoem')->table('data_keyword')->select('key_id','key_keyword')->get();
		$data_keyword = array();
		foreach ($res as $k => $v) { $data_keyword[$v->key_keyword] = $v->key_id; }
		echo 'all data_keyword insert'.PHP_EOL;

		// 唯一标志符号
		$res = Capsule::connection('yp_realoem')->table('data_product')->select('pro_realoem')->get();
		$all_realoem = array();
		foreach ($res as $k => $v) { $all_realoem[] = $v->pro_realoem; }
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
		    // 2、产品表
		    $insert_for_data_product = array();
		    // 3、产品关键词
		    $insert_for_data_product_keyword = array();
		    // 4、产品vin表
		    $insert_for_data_product_vins = array();
		    // 5、产品搜索表
		    $insert_for_data_product_search = array();
		    // 6、主表
		    $insert_for_data_product_partgrp8 = array();
		    // 获取产品最大ID
		    $res = Capsule::connection('yp_realoem')->table('data_product')->select('pro_id')->orderBy('pro_id','desc')->first();
		    $pro_id = $res->pro_id+1;

		    $all_data_id = array();
	        // 循环数据入库
	        foreach ($datas as $key => $data) {
	        	// 校验号码唯一性
	        	if(in_array($data->part_detail_num, $all_realoem)){
	        		continue;
	        	}else{
	        		$all_realoem[] = $data->part_detail_num;
	        	}
	        	// 描述
				if(empty($data->part_detail_des) || empty(str_replace($data->part_detail_des,'',$data->part_detail_prefix)))
				{
					$this_txt =  $data->part_detail_prefix;
				}
				else
				{
		        	$this_txt = str_replace($data->part_detail_des,'',$data->part_detail_prefix).' '.$data->part_detail_des;
				}
	        	// vin
	        	$vin_text = str_replace('http://www.toyodiy.com/parts/q?vin=', '', $data->source);
	        	

	        	// 主组直组关联表主键
	        	$pmg_id = $data_partgrp[sprintf("%s=%s",$data_partgrp1[md5($data->model)],
	        								$data_partgrp2[md5(sprintf('%s=%s',$data->part_type_num,$data->part_type))])]; 


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
			    $insert_for_data_product_vins[] = ['ppv_pro_id'=>$pro_id,'ppv_vin_id'=>$data_vins[$vin_text]];
			    if(!isset($data_text[$this_txt])) {echo $this_txt; die;}
			    // 6、总表
			    $temp = array(
					'ppm_pro_id' => $pro_id,
					'ppm_vin_id' => $data_vins[$vin_text],
					'ppm_pmg_id' => $pmg_id,
					'ppm_ppg_id' => 0,
					'ppm_pos_id' =>'',
					'ppm_txt_id' => $data_text[$this_txt],
					'ppm_order' => 999
			    );
			    $insert_for_data_product_partgrp8[] = $temp;

			    $pro_id++;
			   
			    echo $data->id.PHP_EOL;
	        }
	        // 入库
		    // 2、产品表
		    Capsule::connection('yp_realoem')->table('data_product')->insert($insert_for_data_product);
		    // 3、产品关键词
		    Capsule::connection('yp_realoem')->table('data_product_keyword')->insert($insert_for_data_product_keyword);
		    // 4、产品vin表
		    Capsule::connection('yp_realoem')->table('data_product_vins')->insert($insert_for_data_product_vins);
		    // 5、产品搜索表
		    Capsule::connection('yp_realoem')->table('data_product_search')->insert($insert_for_data_product_search);
		    // 6、主表
		    Capsule::connection('yp_realoem')->table('data_product_partgrp_8')->insert($insert_for_data_product_partgrp8);

		    // 标记已读
		    foreach ($datas as $data) {  $all_data_id[] = $data->id;}
		    if(!empty($all_data_id)) Capsule::table('part_detail')->whereIn('id',$all_data_id)->update(['status' =>'readed']);
		    
			echo 'part_detail 1000 move completed!'.PHP_EOL;

			$empty = Capsule::table('part_detail')->where('status','wait')->get()->isEmpty();
		}	
	}
}
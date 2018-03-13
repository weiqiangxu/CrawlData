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

		// 下载
		Capsule::table('model_detail')->where('status','wait')->orderBy('id')->chunk(10,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'model_detail', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	$guzzle = new guzzle();
		    	$guzzle->phantomjsDown('model_detail',$data);
		    }
		});


		// 解析所有的model_detail获取所有车的数据入库
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

						// 入库基本信息表
						$temp = array(
							'brand' => $data->brand,
							'subbrand' => $data->subbrand,
							'series' => $data->series,
							'model' => $data->model,
							'md5_url' => $data->md5_url
						);


						if($dom->find("#tr_0",0))
						{
							// 厂商
						}
						if($dom->find("#tr_1",0))
						{
							// 级别
						}

						if($dom->find("#tr_2",0))
						{
							// 能源类型
							$temp['nengyuanleixing'] = $dom->find("#tr_2",0)->children(1)->plaintext;
						}

						if($dom->find("#tr_3",0))
						{
							// 上市时间
							$temp['shangshishijian'] = $dom->find("#tr_3",0)->children(1)->plaintext;
						}

						if($dom->find("#tr_4",0))
						{
							// 最大功率（kw）
							$temp['zuidagonglv'] = $dom->find("#tr_4",0)->children(1)->plaintext;
						}

						if($dom->find("#tr_5",0))
						{
							// 最大扭矩（N·m）
							$temp['zuidaniuju'] = $dom->find("#tr_5",0)->children(1)->plaintext;
						}

						if($dom->find("#tr_6",0))
						{
							// 发动机
							$temp['faodngji'] = $dom->find("#tr_6",0)->children(1)->plaintext;
						}

						if($dom->find("#tr_7",0))
						{
							// 变速箱
							$temp['biansuxiang'] = $dom->find("#tr_7",0)->children(1)->plaintext;
						}

						if($dom->find("#tr_8",0))
						{
							// 长*宽*高
							$temp['changkuangao'] = $dom->find("#tr_8",0)->children(1)->plaintext;
						}
						if($dom->find("#tr_9",0))
						{
							// 车身结构
							$temp['cheshenjiegou'] = $dom->find("#tr_9",0)->children(1)->plaintext;
						}

						if($dom->find("#tr_10",0))
						{
							// 最高车速
							$temp['zuigaochesu'] = $dom->find("#tr_10",0)->children(1)->plaintext;
						}

						if($dom->find("#tr_11",0))
						{
							// 官方0-100km/h加速(s)
							$temp['guanfangjiasu'] = $dom->find("#tr_11",0)->children(1)->plaintext;
						}

						if($dom->find("#tr_12",0))
						{
							// 实测0-100km/s加速
							$temp['shicejiasu'] = $dom->find("#tr_12",0)->children(1)->plaintext;
						}

						if($dom->find("#tr_13",0))
						{
							// 实测100km/s-o制动(m)
							$temp['shicezhidong'] = $dom->find("#tr_13",0)->children(1)->plaintext;
						}

						if($dom->find("#tr_14",0))
						{
							// 实测离地间隙（mm）
							$temp['shicelidijianxi'] = $dom->find("#tr_14",0)->children(1)->plaintext;
						}

						if($dom->find("#tr_15",0))
						{
							// 工信部综合油耗（L/100km）
							$temp['gongxinbuyouhao'] = $dom->find("#tr_15",0)->children(1)->plaintext;
						}
						if($dom->find("#tr_16",0))
						{
							// 实测油耗(L/100km)
							$temp['shiceyouhao'] = $dom->find("#tr_16",0)->children(1)->plaintext;
						}

						if($dom->find("#tr_16",0))
						{
							// 整车质保
							$temp['zhengchezhibao'] = "";
						}

						print_r($temp);

						Capsule::commit();

						exit();

			            // 更改SQL语句
			            Capsule::table('model_detail')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'model_detail '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});
	}
}
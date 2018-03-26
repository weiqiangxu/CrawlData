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
  * 检测需要下载的批次并下载相应批次的列表页
  * @author xu
  * @copyright 2018/01/29
  */
class onestep{
	// 初始化所有数据表
	public static function initable()
	{
		// vin表
		if(!Capsule::schema()->hasTable('vin'))
		{
			Capsule::schema()->create('vin', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			  	$table->string('status')->nullable(); 
			});
			echo "table vin create".PHP_EOL;
		}


		// ori_car表
		if(!Capsule::schema()->hasTable('ori_car'))
		{
			Capsule::schema()->create('ori_car', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->text('json')->nullable();
			    $table->string('md5_url')->unique();
			  	$table->string('status')->nullable(); 
			});
			echo "table ori_car create".PHP_EOL;
		}


		// car表
		if(!Capsule::schema()->hasTable('car'))
		{
			Capsule::schema()->create('car', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();

				$table->string('DESTINATION_SIM')->nullable();
				$table->string('DESTINATION')->nullable();
				$table->string('BODY_SIM')->nullable();
				$table->string('BODY')->nullable();
				$table->string('DRIVER_S_POSITION_SIM')->nullable();
				$table->string('DRIVER_S_POSITION')->nullable();
				$table->string('ENGINE_SIM')->nullable();
				$table->string('ENGINE')->nullable();
				$table->string('GEAR_SHIFT_TYPE_SIM')->nullable();
				$table->string('GEAR_SHIFT_TYPE')->nullable();
				$table->string('VIN')->nullable();
				$table->string('MODEL_CODE')->nullable();
				$table->string('FROM')->nullable();
				$table->string('TO')->nullable();
				$table->string('FRAME')->nullable();
				$table->string('Characteristics')->nullable();
				$table->string('GRADE_SIM')->nullable();
				$table->string('GRADE')->nullable();
				$table->string('FUEL_SYSTEM_SIM')->nullable();
				$table->string('FUEL_SYSTEM')->nullable();
				$table->string('TRANSMISSION_SIM')->nullable();
				$table->string('TRANSMISSION')->nullable();
				$table->string('NO_OF_DOORS_SIM')->nullable();
				$table->string('NO_OF_DOORS')->nullable();
				$table->string('BACK_DOOR_SIM')->nullable();
				$table->string('BACK_DOOR')->nullable();
				$table->string('SEATING_CAPACITY_SIM')->nullable();
				$table->string('SEATING_CAPACITY')->nullable();
				$table->string('BUILDING_CONDITION_SIM')->nullable();
				$table->string('BUILDING_CONDITION')->nullable();
				$table->string('MODEL_MARK_SIM')->nullable();
				$table->string('MODEL_MARK')->nullable();
				$table->string('CABIN_SIM')->nullable();
				$table->string('CABIN')->nullable();
				$table->string('GRADE_MARK_SIM')->nullable();
				$table->string('GRADE_MARK')->nullable();
				$table->string('VEHICLE_MODEL_SIM')->nullable();
				$table->string('VEHICLE_MODEL')->nullable();
				$table->string('ROOF_SIM')->nullable();
				$table->string('ROOF')->nullable();
				$table->string('SIDE_WINDOW_SIM')->nullable();
				$table->string('SIDE_WINDOW')->nullable();
				$table->string('PRODUCT_SIM')->nullable();
				$table->string('PRODUCT')->nullable();
				$table->string('CATEGORY_SIM')->nullable();
				$table->string('CATEGORY')->nullable();
				$table->string('EMISSION_REGULATION_SIM')->nullable();
				$table->string('EMISSION_REGULATION')->nullable();
			});
			echo "table car create".PHP_EOL;
		}
		
	}

	// car
	public static function car()
	{	
		$guzzle = new guzzle();

		// 转移
		$url = 'http://www.toyodiy.com/parts/q?vin=';
		$datas = Capsule::connection('vin_list')->table('vin_list')->get();
		foreach ($datas as $k => $v) {
			$temp = ['id'=>$v->mec_id,'md5_url'=>md5($url.$v->mec_code),'url'=>$url.$v->mec_code,'status'=>'wait'];
			$empty = Capsule::table('vin')->where('md5_url',md5($url.$v->mec_code))->get()->isEmpty();
			if($empty) Capsule::table('vin')->insert($temp);
		}


		// 下载
		$empty = Capsule::table('vin')->where('status','wait')->get()->isEmpty();
		@mkdir(PROJECT_APP_DOWN.'vin', 0777, true);
		while(!$empty) {
			$datas = Capsule::table('vin')->where('status','wait')->limit(5)->get();
		    $guzzle->poolRequest('vin',$datas);
		    $empty = Capsule::table('vin')->where('status','wait')->get()->isEmpty();
		}


		// 解析
		$empty = Capsule::table('vin')->where('status','completed')->get()->isEmpty();
		while(!$empty) {
			$datas = Capsule::table('vin')->where('status','completed')->limit(20)->get();
			foreach ($datas as $data) {
				$file = PROJECT_APP_DOWN.'vin/'.$data->id.'.html';
				$prefix = 'http://www.toyodiy.com/';
				if(!file_exists($file))
				{
					echo PROJECT_APP_DOWN.'vin/'.$data->id.'.html not found!'.PHP_EOL;
					continue;
				}
				if($dom = HtmlDomParser::str_get_html(file_get_contents($file)))
				{
					$temp = [];
					if($dom->find('.opts',0) && $dom->find('.opts',0)->find('.t2',0))
					{
						foreach ($dom->find('.opts',0)->find('.t2',0)->find('tr') as $tr) {
							$cloumn = str_replace([' ','\'','.'],'_',rtrim($tr->find('td',0)->plaintext,':'));
							$temp[$cloumn.'_SIM'] = rtrim($tr->find('td',1)->plaintext,':');
							$temp[$cloumn] = $tr->find('td',2)->plaintext;
						}
					}
					else
					{
						 // 更新状态
					    Capsule::table('vin')->where('id', $data->id)->update(['status' =>'error']);
						echo 'vin '.$data->id.' analyse error!'.PHP_EOL;
						continue;
					}
					if($dom->find('.res',0))
					{
						$tr = $dom->find('.res',0)->find('tr',1);
						$temp['VIN'] = $tr->find('td',0)->plaintext;
						$temp['MODEL_CODE'] = $tr->find('td',1)->plaintext;
						$temp['FROM'] = $tr->find('td',2)->plaintext;
						$temp['TO'] = $tr->find('td',3)->plaintext;
						$temp['FRAME'] = $tr->find('td',4)->plaintext;
						$url = $prefix.$tr->find('td',1)->find('a',0)->href;
						$temp['url'] = $url;
						$temp['md5_url'] = md5($url);
						$temp['status'] = 'wait';
						$str = '';
						for ($i=4; $i < 12; $i++) { 
							if($tr->find('td',$i)) $str.=' '.$tr->find('td',$i)->plaintext;	
						}
						$temp['Characteristics'] = $str;
					}

					// 入库
				    $empty = Capsule::table('ori_car')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('ori_car')->insert(['md5_url'=>md5($url),'status'=>'wait','json'=>json_encode($temp)]);	
				    
				    // 更新状态
				    Capsule::table('vin')->where('id', $data->id)->update(['status' =>'readed']);
					echo 'vin '.$data->id.' analyse completed!'.PHP_EOL;
					// 清理内存防止内存泄漏
					$dom-> clear(); 
				}
			}
		    $empty = Capsule::table('vin')->where('status','completed')->get()->isEmpty();
		}

		// 建表并存储
		$datas = Capsule::table('ori_car')->get();
		foreach ($datas as $k => $v) {
			$temp = json_decode($v->json,true);
			// 入库
		    $empty = Capsule::table('car')->where('md5_url',$temp['md5_url'])->get()->isEmpty();
		    if($empty) Capsule::table('car')->insert($temp);
		}
	}
	

}
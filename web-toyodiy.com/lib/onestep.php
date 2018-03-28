<?php
use Illuminate\Database\Capsule\Manager as Capsule;
use Sunra\PhpSimple\HtmlDomParser;
use Huluo\Extend\Gather;
use Illuminate\Database\Schema\Blueprint;
use GuzzleHttp\Client;
/**
  * @author xu
  * @copyright 2018/01/29
  */
class onestep{

	// 初始化表
	public static function initable()
	{
		// vin
		if(!Capsule::schema()->hasTable('vin'))
		{
			Capsule::schema()->create('vin', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('url')->unique();
			  	$table->string('status')->nullable(); 
			});
			echo "table vin create".PHP_EOL;
		}


		// car_json
		if(!Capsule::schema()->hasTable('car_json'))
		{
			Capsule::schema()->create('car_json', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->text('json')->nullable();
			    $table->string('url')->unique()->comment('汽车url');
			  	$table->string('status')->nullable(); 
			});
			echo "table car_json create".PHP_EOL;
		}


		// car
		if(!Capsule::schema()->hasTable('car'))
		{
			Capsule::schema()->create('car', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('url')->unique()->comment('模块URL');
			    $table->string('status')->nullable();
			    $table->string('model')->nullable()->comment('模块名');
			    $table->string('source')->nullable()->comment('vin查询页面地址');
			    // 字段
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


		// car_part
		if(!Capsule::schema()->hasTable('car_part'))
		{
			Capsule::schema()->create('car_part', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('url')->unique()->comment('零件类型地址');
			  	$table->string('car_id')->nullable()->comment('汽车ID'); 
			  	$table->string('status')->nullable();
			  	$table->string('part_type')->nullable()->comment('零件类型');
			  	$table->string('part_type_num')->nullable()->comment('零件类型批次');
			  	$table->string('part_type_page')->nullable()->comment('零件类型页码');
			});
			echo "table car_part create".PHP_EOL;
		}

		// part_detail
		if(!Capsule::schema()->hasTable('part_detail'))
		{
			Capsule::schema()->create('part_detail', function (Blueprint $table){
			    $table->increments('id')->unique();
			  	$table->string('car_id')->nullable()->comment('汽车ID'); 
			  	$table->string('url')->comment('零件类型地址');
			  	$table->string('part_type')->nullable()->comment('零件类型');
			  	$table->string('part_type_num')->nullable()->comment('零件类型-批次');
			  	$table->string('part_type_page')->nullable()->comment('零件类型页码');
			  	$table->string('part_detail_num')->nullable()->comment('号码');
			  	$table->text('part_detail_des')->nullable()->comment('描叙');
			  	$table->string('part_detail_sum')->nullable()->comment('数量');
			});
			echo "table part_detail create".PHP_EOL;
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
			$temp = ['url'=>$url.$v->mec_code,'status'=>'wait'];
			$empty = Capsule::table('vin')->where('url',$url.$v->mec_code)->get()->isEmpty();
			if($empty) Capsule::table('vin')->insert($temp);
		}
		// 下载
		$empty = Capsule::table('vin')->where('status','wait')->get()->isEmpty();
		@mkdir(PROJECT_APP_DOWN.'vin', 0777, true);
		while(!$empty) {
			$datas = Capsule::table('vin')->where('status','wait')->limit(20)->get();
		    $guzzle->poolRequest('vin',$datas);
		    $empty = Capsule::table('vin')->where('status','wait')->get()->isEmpty();
		}
		// 解析
		$empty = Capsule::table('vin')->where('status','completed')->get()->isEmpty();
		while(!$empty) {
			$datas = Capsule::table('vin')->where('status','completed')->limit(20)->get();
			foreach ($datas as $data) {
				$file = PROJECT_APP_DOWN.'vin/'.$data->id.'.html';
				$prefix = 'http://www.toyodiy.com/parts/';
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
						 // 网络错误
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
						$temp['status'] = 'wait';
						$temp['source'] = $data->url;
						$str = '';
						for ($i=4; $i < 12; $i++) { 
							if($tr->find('td',$i)) $str.=' '.$tr->find('td',$i)->plaintext;	
						}
						$temp['Characteristics'] = $str;
					}
					// 唯一校验
				    $empty = Capsule::table('car_json')->where('url',$url)->get()->isEmpty();
				    if($empty) Capsule::table('car_json')->insert(['url'=>$url,'status'=>'wait','json'=>json_encode($temp)]);
				    // 标记已读
				    Capsule::table('vin')->where('id', $data->id)->update(['status' =>'readed']);
					echo 'vin '.$data->id.' analyse completed!'.PHP_EOL;
					// 清楚对象
					$dom-> clear(); 
				}
			}
		    $empty = Capsule::table('vin')->where('status','completed')->get()->isEmpty();
		}

		// 汽车模块URL
		$datas = Capsule::table('car_json')->get();
		foreach ($datas as $k => $v) {
			$temp = json_decode($v->json,true);
			// 四个模块
			for ($i=1; $i < 5; $i++) { 
				$tmp = $temp;
				$url = rtrim($tmp['url'],'.html').'_'.$i.'.html';
				$tmp['url'] = $url;
				if($i==1)
				{
					$tmp['model'] = 'Engine/Fuel';
				}
				elseif($i==2)
				{
					$tmp['model'] = 'Powertrain/Chassis';
				}
				elseif($i==3)
				{
					$tmp['model'] = 'Body';
				}
				elseif($i==4)
				{
					$tmp['model'] = 'Electrical';
				}
				// 唯一校验
				$empty = Capsule::table('car')->where('url',$url)->get()->isEmpty();
			    if($empty) Capsule::table('car')->insert($tmp);
			}
		}
	}
	

}
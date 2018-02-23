<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;

/**
  * @author xu
  * @copyright 2018/01/29
  */
class threestep{

	// 所有的url_market都已经加上筛选条件获取的，此刻url_market的下载链接页面完整，含有所有car的页面
	public static function car()
	{
		// 下载所有的model页面
		Capsule::table('url_market')->where('status','last')->orderBy('id')->chunk(5,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'url_market', 0777, true);
			// 并发请求
		    $guzzle = new guzzle();
		    $guzzle->poolRequest('url_market',$datas,'lastCompleted');
		});


		// 在这个下拉框也就可以获取所有的车型数据-获取所有的车型数据
		Capsule::table('url_market')->where('status','lastCompleted')->orderBy('id')->chunk(5,function($datas){
			$prefix ='https://partsouq.com';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	$file = PROJECT_APP_DOWN.'url_market/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 获取brand页面所有的model
						foreach($dom->find('.search-result-vin tr') as $tr)
						{
							if(!$tr->find("a",0))
							{
								continue;
							}
						    
						    $url = html_entity_decode($prefix.$tr->find("a",0)->href);

							$brand = "";
							if($dom->find("td[data-title=brand]",0))
							{
								$brand = $dom->find("td[data-title=brand]",0)->plaintext;
							}
							$catalog = "";
							if($dom->find("td[data-title=catalog]",0))
							{
								$catalog = $dom->find("td[data-title=catalog]",0)->plaintext;
							}
							$name = "";
							if($dom->find("td[data-title=Name]",0))
							{
								$name = $dom->find("td[data-title=Name]",0)->plaintext;
							}
							$description = "";
							if($dom->find("td[data-title=Description]",0))
							{
								$description = $dom->find("td[data-title=Description]",0)->plaintext;
							}
							$vehicleClass = "";
							if($dom->find("td[data-title=VehicleClass]",0))
							{
								$vehicleClass = $dom->find("td[data-title=VehicleClass]",0)->plaintext;
							}
							$model = "";
							if($dom->find("td[data-title=Model]",0))
							{
								$model = $dom->find("td[data-title=Model]",0)->plaintext;
							}
							$aggregates = "";
							if($dom->find("td[data-title=Aggregates]",0))
							{
								$aggregates = $dom->find("td[data-title=Aggregates] span ",0)->plaintext;
							}
							$market = "";
							if($dom->find("td[data-title=Market]",0))
							{
								$market = $dom->find("td[data-title=Market]",0)->plaintext;
							}

							$modelyearto = "";
							if($dom->find("td[data-title=Modelyearto]",0))
							{
								$modelyearto = $dom->find("td[data-title=Modelyearto]",0)->plaintext;
							}
							$transmission = "";
							if($dom->find("td[data-title=Transmission]",0))
							{
								$transmission = $dom->find("td[data-title=Transmission]",0)->plaintext;
							}
							$grade = "";
							if($dom->find("td[data-title=Grade]",0))
							{
								$grade = $dom->find("td[data-title=Grade]",0)->plaintext;
							}
							$engine = "";
							if($dom->find("td[data-title=Engine]",0))
							{
								$engine = $dom->find("td[data-title=Engine]",0)->plaintext;
							}
							$options = "";
							if($dom->find("td[data-title=Options]",0))
							{
								$options = $dom->find("td[data-title=Options]",0)->plaintext;
							}
							$modelyearfrom = "";
							if($dom->find("td[data-title=Modelyearfrom]",0))
							{
								$modelyearfrom = $dom->find("td[data-title=Modelyearfrom]",0)->plaintext;
							}
							$bodyStyle = "";
							if($dom->find("td[data-title=BodyStyle]",0))
							{
								$bodyStyle = $dom->find("td[data-title=BodyStyle]",0)->plaintext;
							}
							$options = "";
							if($dom->find("td[data-title=Options]",0))
							{
								$options = $dom->find("td[data-title=Options]",0)->plaintext;
							}
							
							
							$temp = array(
								'brand'=>$brand,
								'catalog'=>$catalog,
								'name'=>$name,
								'market'=>$market,
								'description'=>$description,
								'vehicleClass'=>$vehicleClass,
								'transmission'=>$transmission,
								'engine'=>$engine,
								'model'=>$model,
								'bodyStyle'=>$bodyStyle,
								'aggregates'=>$aggregates,
								'modelyearto'=>$modelyearto,
								'grade'=>$grade,
								'options'=>$options,
								'modelyearfrom'=>$modelyearfrom,
								'url' =>$url,
								'md5_url'=>md5($url),
								'status'=>'wait',
								'options' => $options
							);
							// print_r($temp);die;
							$empty = Capsule::table('carinfo')
						    	->where('md5_url',md5($url))
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
							    Capsule::table('carinfo')->insert($temp);					    	
						    }
						}
			            // 更改SQL语句
			            Capsule::table('url_market')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'url_market '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});
	}
}
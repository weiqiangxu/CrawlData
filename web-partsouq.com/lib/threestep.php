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

		// 在这个下拉框也就可以获取所有的车型数据-获取所有的车型数据
		Capsule::table('url_market')->where('status','last')->orderBy('id')->chunk(5,function($datas){
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
						// 读取汽车列表页的每一辆汽车
						foreach($dom->find('.search-result-vin tr') as $tr)
						{
							if(!$tr->find("a",0))
							{
								continue;
							}
						    // 汽车详情页链接
						    $url = $prefix.$tr->find("a",0)->href;
						    // 汽车栏目信息
							$CatalogBrand = '';
							$CatalogName = '';
							$CatalogCode = '';
							$Name = '';
							$Transmission = '';
							$SeriesCode = '';
							$Engine = '';
							$BodyStyle = '';
							$Steering = '';
							$Model = '';
							$SeriesDescription = '';
							$Doors = '';
							$Country = '';
							$Grade = '';
							$Region = '';
							$CountryDecode = '';
							$Manufactured = '';
							$ModelYearTo = '';
							$OptionS = '';
							$Family = '';
							$VehicleCategory = '';
							$ModelYearFrom = '';
							$Market = '';
							$Autoid = '';
							$Description = '';
							$ProdPeriod = '';
							$CarLine = '';
							$DestinationRegion = '';
							$Datefrom = '';
							$ModelYear = '';
							$Drive = '';
							$CatalogNo = '';
							$VehicleClass = '';
							$Aggregates = '';
							$FrameS = '';
							$Modification = '';
							$VehicleType = '';
							$Type = '';

							// 获取当前列表页汽车所属品牌
							$CatalogBrand = $dom->find(".table-bordered-n tbody",0)->last_child()->find("h4",0)->plaintext;
							$CatalogName = $dom->find(".table-bordered-n tbody",0)->last_child()->find("td",2)->plaintext;
							$CatalogCode = $dom->find(".table-bordered-n tbody",0)->last_child()->find("td",3)->plaintext;

							// dom解析获取某辆汽车信息
							$title = array(
								'Name',
								'Transmission',
								'Series_code',
								'Engine',
								'BodyStyle',
								'Steering',
								'Model',
								'Series_description',
								'Doors',
								'Country',
								'Grade',
								'Region',
								'CountryDecode',
								'Manufactured',
								'Modelyearto',
								'Options',
								'Family',
								'VehicleCategory',
								'Modelyearfrom',
								'Market',
								'Autoid',
								'Description',
								'ProdPeriod',
								'CarLine',
								'Destinationregion',
								'Datefrom',
								'ModelYear',
								'Drive',
								'CatalogNo',
								'VehicleClass',
								'Aggregates',
								'Frames',
								'Modification',
								'VehicleType',
								'Type',
							);

							$temp = array();

							foreach ($title as $k => $v)
							{
								if($dom->find("td[data-title=".$v."]",0))
								{
									$temp[$v] = $dom->find("td[data-title=".$v."]",0)->plaintext;
								}
								else
								{
									$temp[$v] = '';
								}
							}

							// 汽车分类数据
							$temp['CatalogBrand'] = $CatalogBrand;
							$temp['CatalogName'] = $CatalogName;
							$temp['CatalogCode'] = $CatalogCode;
							// url信息
							$temp['url'] = $url;
							$temp['md5_url'] = md5($url);
							$temp['status'] = 'wait';

							// 插入数据
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
					            ->update(['status' =>'lastReaded']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'url_market '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});
	}
}
<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;

/**
  * 下载所有零件详情页面
  * @author xu
  * @copyright 2018/01/29
  */
class threestep{

	public static function download()
	{
		// 下载所有的配件详情页面
		Capsule::table('url_pic')->where('status','wait')->orderBy('id')->chunk(20,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'url_pic', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'url_pic/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!file_exists($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curl_https($data->url);
		    		if($res['info']['http_code']== 200)
		    		{
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'url_pic '.$data->id.'.html'." download successful!\r\n";
		    		}
		    	}
		    	if(file_exists($file))
		    	{
		            // 更改SQL语句
		            Capsule::table('url_pic')
				            ->where('id', $data->id)
				            ->update(['status' =>'completed']);
		    	}
		    }
		});
	}


	public static function analyse()
	{
		// 现在解析pic html获取所有的零件相关信息
		Capsule::table('url_pic')->where('status','completed')->orderBy('id')->chunk(20,function($datas){
			$prefix = 'https://partsouq.com';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	$file = PROJECT_APP_DOWN.'url_pic/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						$temp = array();
						// 解析入库
						$brand = $dom->find(".table-bordered-n tbody",0)->last_child()->find("h4",0)->plaintext;
						$code =  $dom->find(".table-bordered-n tbody tr",1)->last_child()->plaintext;
						$name = $dom->find("td[data-title=Name]",0)->outertext;
						$Destinationregion = $dom->find("td[data-title=Destinationregion]",0)->outertext;
						$Transmission = $dom->find("td[data-title=Transmission]",0)->outertext;
						$Series_code = $dom->find("td[data-title=Series_code]",0)->outertext;
						$Engine = $dom->find("td[data-title=Engine]",0)->outertext;
						$BodyStyle = $dom->find("td[data-title=BodyStyle]",0)->outertext;
						$Steering = $dom->find("td[data-title=Steering]",0)->outertext;
						$Model = $dom->find("td[data-title=Model]",0)->outertext;
						$Series_description = $dom->find("td[data-title=Series_description]",0)->outertext;
						$simple = $dom->find(".col-xs-8 h4",0)->plaintext;
						// 图片路由
						$image = $dom->find("img[data-container=zoom_container_0]",0)->src;
						var_dump($image);die;


						// 获取brand页面所有的model
						foreach($dom->find('.accordion-toggle') as $article)
						{
						    // 存储进去所有的&model
						    $temp = [
						    	'url' => html_entity_decode($prefix.$article->href),
						    	'status' => 'wait',
						    ];
						    $empty = Capsule::table('url_model')
						    	->where('url',html_entity_decode($prefix.$article->href))
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
							    Capsule::table('url_model')->insert($temp);					    	
						    }
						}
			            // 更改SQL语句
			            Capsule::table('url_brand')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'brand '.$data->id.'.html'."  analyse successful!\r\n";
					}
		    	}
		    }
		});
	}
}
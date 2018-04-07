<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;


use Illuminate\Database\Schema\Blueprint;

/**
  * 下载所有零件详情页面
  * @author xu
  * @copyright 2018/01/29
  */
class sevenstep{

	public static function analyse()
	{
		// 现在解析pic html获取所有的零件相关信息
		Capsule::table('url_pic')->where('status','completed')->orderBy('id')->chunk(5,function($datas){
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
						// 获取零件所在目录
						$simple = "";
						if($dom->find(".col-xs-8 h4",0))
						{
							$simple = $dom->find(".col-xs-8 h4",0)->plaintext;
						}

						// 图片路由
						$image = $dom->find("img[data-container=zoom_container_0]",0)->src;
						$image = $prefix.$image;
						// 获取图片上的点的介绍
						$pic_des = array();
						$pic_des_key = array();
						if($dom->find('.bottom-block-table th'))
						{
							foreach($dom->find('.bottom-block-table th') as $v)
							{
								$pic_des_key[] = $v->plaintext;
							}
							foreach($dom->find('.part-search-tr') as $v)
							{
								$arr = array();
								for ($i=0; $i < count($pic_des_key); $i++)
								{ 
									$arr[$pic_des_key[$i]] = $v->children($i)->plaintext;
								}
								$pic_des[] = $arr;
							}
						}
						$part_detail = serialize($pic_des);
						// 拼接入库
						$temp = array(
							'simple' => $simple,
							'image' => $image,
							'part_detail' => $part_detail,
							'url' => $data->url,
							'url_md5' => md5($data->url),							
							'car_id' => $data->car_id
						);
						// 校验是否已经存在
						$empty = Capsule::table('carparts')
					    	->where('url_md5',md5($data->url))
					    	->get()
					    	->isEmpty();
					    if($empty)
					    {
						    Capsule::table('carparts')->insert($temp);					    	
				            // 更改SQL语句
				            Capsule::table('url_pic')
						            ->where('id', $data->id)
						            ->update(['status' =>'readed']);
						    // 输出日志信息
				            echo 'url_pic '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					    }
					}
		    	}
		    }
		});
	}
}
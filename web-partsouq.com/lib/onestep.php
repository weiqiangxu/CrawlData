<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;

/**
  * 检测需要下载的批次并下载相应批次的列表页
  * @author xu
  * @copyright 2018/01/29
  */
class onestep{


	// 初始化所有数据表
	public static function initable()
	{
		// url_brand表
		if(!Capsule::schema()->hasTable('url_brand'))
		{
			Capsule::schema()->create('url_brand', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable()->unique();
			    $table->string('status')->nullable();
			});
			echo "table url_brand create\r\n";
		}
		// url_model表
		if(!Capsule::schema()->hasTable('url_model'))
		{
			Capsule::schema()->create('url_model', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable()->unique();
			    $table->string('status')->nullable();
			});
			echo "table url_model create\r\n";
		}
		// url_car表
		if(!Capsule::schema()->hasTable('url_car'))
		{
			Capsule::schema()->create('url_car', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable()->unique();
			    $table->string('status')->nullable();
			});
			echo "table url_car create\r\n";
		}
		// url_part
		if(!Capsule::schema()->hasTable('url_part'))
		{
			Capsule::schema()->create('url_part', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable()->unique();
			    $table->string('status')->nullable();
			});
			echo "table url_part create\r\n";
		}
		// url_pic
		if(!Capsule::schema()->hasTable('url_pic'))
		{
			Capsule::schema()->create('url_pic', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			});
			echo "table url_pic create\r\n";
		}
		// rawdata
		if(!Capsule::schema()->hasTable('rawdata'))
		{
			Capsule::schema()->create('rawdata', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('simple')->nullable();
			    $table->string('brand')->nullable();
			    $table->string('code')->nullable();
			    $table->string('name')->nullable();
			    $table->string('destination')->nullable();
			    $table->string('transmission')->nullable();
			    $table->string('series_code')->nullable();
			    $table->string('engine')->nullable();
			    $table->string('bodystyle')->nullable();
			    $table->string('steering')->nullable();
			    $table->string('model')->nullable();
			    $table->string('series_description')->nullable();
			    // 描述图片地址
			    $table->text('image')->nullable();
			    // 配件左侧介绍信息json格式存储 {1:msg1,2:msg2}
			    $table->longText('part_detail')->nullable();
			    // 页面网址
			    $table->text('url')->nullable();
			    // 页面网址md5数值用于防止重复
			    $table->string('url_md5')->unique();
			});
			echo "table rawdata create\r\n";
		}
	}

	// // 获取所有如下链接=>url_brand
	// https://partsouq.com/en/catalog/genuine/locate?c=BMW
	public static function brand()
	{
		// 解析首页
		$prefix = 'https://partsouq.com/catalog/genuine';
		$prefix_catalog ='https://partsouq.com';
		$mineload = new mineload();
		$res = $mineload->curl_https($prefix);

		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html($res['html']))
		{
			foreach($dom->find('tbody h4') as $article)
			{
				if(!$article->find("a",0))
				{
					// 排除<h4>Login</h4>按钮
					continue;
				}
			    // 存储进去所有的&body
			    $temp = [
			    	'url' => $prefix_catalog.$article->find("a",0)->href,
			    	'status' => 'wait',
			    ];
			    $empty = Capsule::table('url_brand')
			    	->where('url',$prefix_catalog.$article->find("a",0)->href)
			    	->get()
			    	->isEmpty();
			    if($empty)
			    {
				    Capsule::table('url_brand')->insert($temp);					    	
			    }
			}
			echo 'url_brand analyse completed!'."\r\n";
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
		else
		{
			exit('net error!');
		}
	}

	// 获取所有如下链接=>url_model
	// https://partsouq.com/en/catalog/genuine/pick?c=Lexus&model=CT200H&ssd=%24Wl05BAc%24
	public static function model()
	{
		// 下载所有的brand页面
		Capsule::table('url_brand')->where('status','wait')->orderBy('id')->chunk(20,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'url_brand', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'url_brand/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!file_exists($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curl_https($data->url);
		    		if($res['info']['http_code']== 200 )
		    		{
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'brand '.$data->id.'.html'." download successful!\r\n";
		    		}
		    	}

		    	if(file_exists($file))
		    	{
		            // 更改SQL语句
		            Capsule::table('url_brand')
				            ->where('id', $data->id)
				            ->update(['status' =>'completed']);
		    	}

		    }
		});
		// 现在解析brand html获取所有的model的url
		Capsule::table('url_brand')->where('status','completed')->orderBy('id')->chunk(20,function($datas){
			$prefix = 'https://partsouq.com';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	// 保存文件名
		    	$file = PROJECT_APP_DOWN.'url_brand/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
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
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
		// url_market表
		if(!Capsule::schema()->hasTable('url_market'))
		{
			Capsule::schema()->create('url_market', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('level')->nullable()->default(0);
			    $table->string('status')->nullable();
			});
			echo "table url_market create\r\n";
		}
		// url_car表
		if(!Capsule::schema()->hasTable('url_car'))
		{
			Capsule::schema()->create('url_car', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			});
			echo "table url_car create\r\n";
		}
		// url_part
		if(!Capsule::schema()->hasTable('url_part'))
		{
			Capsule::schema()->create('url_part', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
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

			    $table->string('Grade')->nullable();
			    $table->string('Options')->nullable();
			    $table->string('Modelyearfrom')->nullable();


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
	// // 获取所有如下链接=>url_market
	// https://partsouq.com/en/catalog/genuine/locate?c=BMW
	public static function market()
	{
		// 解析首页
		$prefix = 'https://partsouq.com/en/catalog/genuine/filter?c=';
		$client = new Client();
		$response = $client->get('https://partsouq.com/catalog/genuine',['verify' => false]);
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html($response->getBody()))
		{
			foreach($dom->find('tbody h4') as $article)
			{
				if(!$article->find("a",0))
				{
					// 排除<h4>Login</h4>按钮
					continue;
				}
				// 加限定只要Nissan的数据
				if(!strpos($article->find("a",0)->href, 'Nissan'))
				{
					continue;
				}

				// 获取当前品牌
				$href = explode("?c=", $article->find("a",0)->href);
				if(is_array($href))
				{
					$href = end($href);
				}
				else
				{
					continue;
				}
			    // 存储进去所有的&body
			    $temp = [
			    	'url' => $prefix.$href,
			    	'status' => 'wait',
			    	'md5_url' => md5($prefix.$href)
			    ];
			    $empty = Capsule::table('url_market')
			    	->where('md5_url',md5($prefix.$href))
			    	->get()
			    	->isEmpty();
			    if($empty)
			    {
				    Capsule::table('url_market')->insert($temp);					    	
			    }
			}
			echo 'url_market analyse completed!'."\r\n";
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
		else
		{
			exit('net error!');
		}
	}
	

}
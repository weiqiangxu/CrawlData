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
			echo "table url_market create".PHP_EOL;
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
			echo "table url_car create".PHP_EOL;
		}
		// url_part
		if(!Capsule::schema()->hasTable('url_part'))
		{
			Capsule::schema()->create('url_part', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('car_id')->nullable();
			});
			echo "table url_part create".PHP_EOL;
		}
		// url_pic
		if(!Capsule::schema()->hasTable('url_pic'))
		{
			Capsule::schema()->create('url_pic', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('car_id')->nullable();
			});
			echo "table url_pic create".PHP_EOL;
		}
		// carparts
		if(!Capsule::schema()->hasTable('carparts'))
		{
			Capsule::schema()->create('carparts', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('simple')->nullable();
			    $table->string('brand')->nullable();
			    $table->string('name')->nullable();
			    $table->string('code')->nullable();
			    $table->string('car_id')->nullable();
			    // 描述图片地址
			    $table->text('image')->nullable();
			    // 配件左侧介绍信息json格式存储 {1:msg1,2:msg2}
			    $table->longText('part_detail')->nullable();
			    // 页面网址
			    $table->text('url')->nullable();
			    // 页面网址md5数值用于防止重复
			    $table->string('url_md5')->unique();
			});
			echo "table carparts create".PHP_EOL;
		}

		// carinfo
		if(!Capsule::schema()->hasTable('carinfo'))
		{
			Capsule::schema()->create('carinfo', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('brand')->nullable();
			    $table->string('catalog')->nullable();
			    $table->string('name')->nullable();
			    $table->string('market')->nullable();
			    $table->string('description')->nullable();
			    $table->string('vehicleClass')->nullable();
			    $table->string('model')->nullable();
			    $table->text('aggregates')->nullable();
			    $table->string('modelyearto')->nullable();
			    $table->string('transmission')->nullable();
			    $table->text('engine')->nullable();
				$table->string('grade')->nullable();
			    $table->string('bodyStyle')->nullable();
			    $table->text('modelyearfrom')->nullable();
			    $table->text('url')->nullable();
			    $table->string('md5_url')->unique();
			    $table->string('options')->nullable();
			    $table->string('status')->nullable();
			});
			echo "table carinfo create".PHP_EOL;
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
			echo 'url_market analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
		else
		{
			exit('net error!');
		}
	}
	

}
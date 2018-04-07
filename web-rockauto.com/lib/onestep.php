<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;

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
		// brand表
		if(!Capsule::schema()->hasTable('brand'))
		{
			Capsule::schema()->create('brand', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('brand')->nullable();
			});
			echo "table brand create".PHP_EOL;
		}
		// year表
		if(!Capsule::schema()->hasTable('year'))
		{
			Capsule::schema()->create('year', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('brand')->nullable();
			    $table->string('year')->nullable();
			});
			echo "table year create".PHP_EOL;
		}
		// model表
		if(!Capsule::schema()->hasTable('model'))
		{
			Capsule::schema()->create('model', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('brand')->nullable();
			    $table->string('year')->nullable();
			    $table->string('model')->nullable();
			});
			echo "table model create".PHP_EOL;
		}
		// engine表
		if(!Capsule::schema()->hasTable('engine'))
		{
			Capsule::schema()->create('engine', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('brand')->nullable();
			    $table->string('year')->nullable();
			    $table->string('model')->nullable();
			    $table->string('engine')->nullable();

			});
			echo "table engine create".PHP_EOL;
		}


	}
	// 获取所有品牌连接
	// http://www.rockauto.com/en/catalog/abarth
	public static function brand()
	{
		$prefix = 'http://www.rockauto.com';
		// 解析首页
		$client = new Client();
		$response = $client->get('http://www.rockauto.com/en/catalog',['proxy'=> "http://127.0.0.1:9668",]);
		
		@mkdir(PROJECT_APP_DOWN, 0777, true);
		// 保存首页
		file_put_contents(PROJECT_APP_DOWN.'index.html', $response->getBody());
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.'index.html')))
		{
			foreach($dom->find('.ranavnode .navlabellink') as $li)
			{
				$url = $prefix.$li->href;
			    // 存储
			    $temp = [
			    	'url' => $url,
			    	'status' => 'wait',
			    	'md5_url' => md5($url),
			    	'brand' => $li->plaintext
			    ];
			    $empty = Capsule::table('brand')
			    	->where('md5_url',md5($url))
			    	->get()
			    	->isEmpty();
			    if($empty)
			    {
				    Capsule::table('brand')->insert($temp);					    	
			    	echo $url.' insert completed!'.PHP_EOL;
			    }
			}
			echo 'brand analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
		else
		{
			exit('Net Error!');
		}
	}
	

}
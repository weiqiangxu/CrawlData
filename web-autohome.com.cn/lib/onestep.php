<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;

use Illuminate\Database\Schema\Blueprint;
use GuzzleHttp\Client;

/**
  * 解析首页
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
			    $table->string('brand')->nullable()->comment('品牌');
			});
			echo "table brand create".PHP_EOL;
		}
		// series
		if(!Capsule::schema()->hasTable('series'))
		{
			Capsule::schema()->create('series', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('brand')->nullable()->comment('品牌');
			    $table->string('subbrand')->nullable()->comment('子品牌');
			    $table->string('series')->nullable()->comment('车系');
			    $table->integer('series_num')->nullable()->comment('车型数量');
			});
			echo "table series create".PHP_EOL;
		}
		// model_list
		if(!Capsule::schema()->hasTable('model_list'))
		{
			Capsule::schema()->create('model_list', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('brand')->nullable()->comment('品牌');
			    $table->string('subbrand')->nullable()->comment('子品牌');
			    $table->string('series')->nullable()->comment('车系');
			    $table->string('onsell')->nullable()->comment('在售/停售/预售');
			});
			echo "table model_list create".PHP_EOL;
		}
		// model_detail
		if(!Capsule::schema()->hasTable('model_detail'))
		{
			Capsule::schema()->create('model_detail', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('brand')->nullable()->comment('品牌');
			    $table->string('subbrand')->nullable()->comment('子品牌');
			    $table->string('series')->nullable()->comment('车系');
			    $table->string('model')->nullable()->comment('车型');
			    $table->string('onsell')->nullable()->comment('在售/停售/预售');
			});
			echo "table model_detail create".PHP_EOL;
		}

		// param
		if(!Capsule::schema()->hasTable('param'))
		{
			Capsule::schema()->create('param', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('model_detail_id')->integer()->comment('车型ID');
			    $table->string('pram_lable')->string()->comment('参数类别');
			    $table->string('pram_key')->string()->comment('参数中文名');
			    $table->string('pram_val')->string()->comment('参数值');
			});
			echo "table param create".PHP_EOL;
		}

	}
	// 获取品牌链接
	public static function brand()
	{
		$prefix = 'https://car.autohome.com.cn';
		$client = new Client();
		$response=$client->get('https://car.autohome.com.cn/AsLeftMenu/As_LeftListNew.ashx?typeId=1%20&brandId=0%20&fctId=0%20&seriesId=0',['verify' => false]);
		@mkdir(PROJECT_APP_DOWN, 0777, true);
		// 保存首页
		file_put_contents(PROJECT_APP_DOWN.'index.html', $response->getBody());
		$html = rtrim(ltrim(file_get_contents(PROJECT_APP_DOWN.'index.html'),'document.writeln("'),'");');
		$html = mb_convert_encoding($html,"UTF-8", "gb2312");
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html($html))
		{
			foreach($dom->find('li a') as $a)
			{
				$brandId =rtrim(ltrim($a->href,'/price/brand-'),'.html');
				$url = $prefix.'/AsLeftMenu/As_LeftListNew.ashx?typeId=1&brandId='.$brandId.'&fctId=0&seriesId=0';
			    // 存储
			    $temp = [
			    	'url' => $url,
			    	'status' => 'wait',
			    	'md5_url' => md5($url),
			    	'brand' => preg_replace('/\(\d+\)/','',$a->plaintext)
			    ];
			    $empty = Capsule::table('brand')->where('md5_url',md5($url))->get()->isEmpty();
			    if($empty) Capsule::table('brand')->insert($temp);
			}
			echo 'brand analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}
}
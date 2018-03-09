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
  * 获取需要下载的品牌链接
  * @author xu
  * @copyright 2018/01/29
  */
class onestep{
	// 初始化所有数据表
	public static function initable()
	{
		// brand_one表
		if(!Capsule::schema()->hasTable('brand_one'))
		{
			Capsule::schema()->create('brand_one', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('brand_one')->nullable();
			});
			echo "table brand_one create".PHP_EOL;
		}

		// brand_two表
		if(!Capsule::schema()->hasTable('brand_two'))
		{
			Capsule::schema()->create('brand_two', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('brand_one')->nullable();
			    $table->string('brand_two')->nullable();
			});
			echo "table brand_two create".PHP_EOL;
		}

	}
	// 获取所有品牌连接
	// http://www.rockauto.com/en/catalog/abarth
	public static function brand_one()
	{
		$prefix = 'https://car.autohome.com.cn';
		// 解析首页
		$client = new Client();
		
		$config = [
			'verify' => false,
			// 'proxy'=> "http://127.0.0.1:9668"
		];
		$response = $client->get('https://car.autohome.com.cn/AsLeftMenu/As_LeftListNew.ashx?typeId=1%20&brandId=0%20&fctId=0%20&seriesId=0',$config);
		
		@mkdir(PROJECT_APP_DOWN, 0777, true);
		// 保存首页
		file_put_contents(PROJECT_APP_DOWN.'index.html', $response->getBody());

		$html = file_get_contents(PROJECT_APP_DOWN.'index.html');

		$html = ltrim($html,'document.writeln("');
		$html = rtrim($html,'");');

		// 字符编码转换
		$html = mb_convert_encoding($html,"UTF-8", "gb2312");
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html($html))
		{
			foreach($dom->find('li a') as $a)
			{
				$brandId =rtrim(ltrim($a->href,'/price/brand-'),'.html');

				$url = $prefix.'/AsLeftMenu/As_LeftListNew.ashx?typeId=1&brandId='.$brandId.'&fctId=0 &seriesId=0';
			    // 存储
			    $temp = [
			    	'url' => $url,
			    	'status' => 'wait',
			    	'md5_url' => md5($url),
			    	'brand_one' => preg_replace('/\(\d+\)/','',$a->plaintext)
			    ];
			    $empty = Capsule::table('brand_one')
			    	->where('md5_url',md5($url))
			    	->get()
			    	->isEmpty();
			    if($empty)
			    {
				    Capsule::table('brand_one')->insert($temp);					    	
			    	echo $url.' insert completed!'.PHP_EOL;
			    }
			}
			echo 'brand_one analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
		else
		{
			exit('Net Error!');
		}
	}
	

}
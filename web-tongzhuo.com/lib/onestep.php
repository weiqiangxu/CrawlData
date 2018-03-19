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
		// brand表
		if(!Capsule::schema()->hasTable('info'))
		{
			Capsule::schema()->create('info', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('title')->nullable();
			    $table->string('des')->nullable();
			});
			echo "table info create".PHP_EOL;
		}

	}


	// bris_ac_uk
	public static function bris_ac_uk()
	{
		$web = 'http://www.bris.ac.uk/study/postgraduate/search/';
		// 解析页面
		$client = new Client();
		$response = $client->get($web);
		
		@mkdir(PROJECT_APP_DOWN, 0777, true);
		// 保存首页
		file_put_contents(PROJECT_APP_DOWN.'index.html', $response->getBody());
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.'index.html')))
		{
			foreach($dom->find('.prog-results-list li') as $li)
			{
				$url = 'http://www.bris.ac.uk'.$li->find('a',0)->href;
			    // 存储
			    $temp = [
			    	'url' => $url,
			    	'status' => 'wait',
			    	'md5_url' => md5($url),
			    	'title' => $li->find('a',0)->plaintext,
			    	'des' => $li->find('.prog-type',0)->plaintext
			    ];
			    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
			    // 入库
			    if($empty) Capsule::table('info')->insert($temp);
			}
			echo 'bris_ac_uk analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}
	

}
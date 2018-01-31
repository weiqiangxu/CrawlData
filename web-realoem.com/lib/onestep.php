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
		// model表
		if(!Capsule::schema()->hasTable('url_model'))
		{
			Capsule::schema()->create('url_model', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable()->unique();
			    $table->string('status')->nullable();
			});
			echo "table url_model create\r\n";
		}

		// market表
		if(!Capsule::schema()->hasTable('url_market'))
		{
			Capsule::schema()->create('url_market', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable()->unique();
			    $table->string('status')->nullable();
			});
			echo "table url_market create\r\n";
		}

		// prod表
		if(!Capsule::schema()->hasTable('url_prod'))
		{
			Capsule::schema()->create('url_prod', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable()->unique();
			    $table->string('status')->nullable();
			});
			echo "table url_prod create\r\n";
		}

		// engine表
		if(!Capsule::schema()->hasTable('url_engine'))
		{
			Capsule::schema()->create('url_engine', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable()->unique();
			    $table->string('status')->nullable();
			});
			echo "table url_engine create\r\n";
		}

		// engine表
		if(!Capsule::schema()->hasTable('url_steering'))
		{
			Capsule::schema()->create('url_steering', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable()->unique();
			    $table->string('status')->nullable();
			});
			echo "table url_steering create\r\n";
		}

		// engine表
		if(!Capsule::schema()->hasTable('rawdata'))
		{
			Capsule::schema()->create('rawdata', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('series')->nullable();
			    $table->string('body')->nullable();
			    $table->string('model')->nullable();
			    $table->string('market')->nullable();
			    $table->string('prod')->nullable();
			    $table->string('engine')->nullable();
			    $table->string('steering')->nullable();
			    $table->string('code')->nullable();
			    $table->string('name')->nullable();
			    $table->string('url')->nullable()->unique();
			});
			echo "table rawdata create\r\n";
		}


	}

	// 获取所有的&body
	public static function body()
	{
		if(Capsule::schema()->hasTable('url_body'))
		{
			echo " ini table body data successful\r\n";
			return true;
		}

		// 检测数据库是否存在如果不存在就删除
		if(!Capsule::schema()->hasTable('url_body'))
		{
			Capsule::schema()->create('url_body', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable()->unique();
			    $table->string('status')->nullable();
			});
			echo "table url_body create\r\n";
		}

		$LibFile = new LibFile();
		// 记录第1步骤日志
		$logFile = PROJECT_APP_DOWN.'onestep.txt';
		// 解析首页
		$prefix = 'http://www.realoem.com/bmw/enUS/select?product=P&archive=0';
		$mineload = new mineload();
		$res = $mineload->curldownpage($prefix);

		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html($res['html']))
		{

			$articles = array();
			foreach($dom->find('#series option') as $article) {
			    $articles[] = $article->value;
			}
			array_unique($articles);
			$newarticles = array();
			foreach ($articles as $k => $v)
			{
				$newarticles[] = $v;
			}

			$LibFile->WriteData($logFile, 4, 'series 分析完毕！');
			echo 'series analyse completed!'."\r\n";
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
		else
		{
			exit('net error!');
		}


		// 创建文件夹
		@mkdir(PROJECT_APP_DOWN.'series', 0777, true);
		// 下载所有的series页面
		foreach ($newarticles as $k => $v)
		{

			$mineload = new mineload();
			$res = $mineload->curldownpage($prefix.'&series='.$v);

			if($res['info']['http_code']=='200')
			{
				// 存储
				file_put_contents(PROJECT_APP_DOWN.'series/'.$v.'.html',$res['html']);
				// 记录成功
				echo '&series='.$v." download successful\r\n";
			    $LibFile->WriteData($logFile, 4, ' series='.$v.' 下载完成！');
			}
			else
			{
				// 记录失败
				echo '&series='.$v." download error\r\n";
			    $LibFile->WriteData($logFile, 4, 'series='.$v.' 下载失败！');
			}

		}
		
		// 获取所有的bodyurl
		foreach ($newarticles as $k => $v)
		{
			$temp = file_get_contents(PROJECT_APP_DOWN.'series/'.$v.'.html');

			if($dom = HtmlDomParser::str_get_html($temp))
			{
				// 获取所有的body
				foreach($dom->find('#body option') as $article)
				{
				    // $articles[] = $article->value;
				    $url = $prefix.'&series='.$v.'&body='.$article->value;
				    // 存储进去所有的&body
				    $data = [
				    	'url' => $url,
				    	'status' => 'wait'
				    ];
				    Capsule::table('url_body')->insert($data);
				}
				echo '&series='.$v." analyse successful\r\n";
				$LibFile->WriteData($logFile, 4, ' series='.$v.' 解析完成！');
			}
		}
	}

	
}
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

		// series表
		if(!Capsule::schema()->hasTable('url_series'))
		{
			Capsule::schema()->create('url_series', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable()->unique();
			    $table->string('status')->nullable();
			});
			echo "table url_series create\r\n";
		}

		// body
		if(!Capsule::schema()->hasTable('url_body'))
		{
			Capsule::schema()->create('url_body', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable()->unique();
			    $table->string('status')->nullable();
			});
			echo "table url_body create\r\n";
		}

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

			    $table->string('series2')->nullable();
			    $table->string('body2')->nullable();
			    $table->string('model2')->nullable();
			    $table->string('market2')->nullable();
			    $table->string('prod2')->nullable();
			    $table->string('engine2')->nullable();
			    $table->string('steering2')->nullable();

			    $table->string('hidden')->nullable();

			    $table->string('url')->nullable()->unique();
			});
			echo "table rawdata create\r\n";
		}


	}

	// 获取所有的&body
	public static function body()
	{

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
			foreach($dom->find('#series option') as $article)
			{
			    // 存储进去所有的&body
			    $temp = [
			    	'url' => $prefix.'&series='.$article->value,
			    	'status' => 'wait',
			    ];
			    $empty = Capsule::table('url_series')
			    	->where('url',$prefix.'&series='.$article->value)
			    	->get()
			    	->isEmpty();
			    if($empty)
			    {
				    Capsule::table('url_series')->insert($temp);					    	
			    }
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

		// 下载所有的series页面
		Capsule::table('url_series')->where('status','wait')->orderBy('id')->chunk(20,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'series', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'series/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!file_exists($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curldownpage($data->url);
		    		if(strpos($res['html'],"You have accessed our pages too fast" ))
		    		{
		    			// 返回空文件跳出循环
		    			continue;
		    		}
		    		if($res['info']['http_code']== 200 )
		    		{
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            
		    		}
		            // 命令行执行时候不需要经过apache直接输出在窗口
		            echo 'series '.$data->id.'.html'."  download successful!\r\n";
		            // 记录成功
		            $LibFile->WriteData($logFile, 4,'series '.$data->id.'.html'.'下载完成！');
		    	}
		    	if(file_exists($file))
		    	{
		            // 更改SQL语句
		            Capsule::table('url_series')
				            ->where('id', $data->id)
				            ->update(['status' =>'completed']);
				}
		    }
		});


		// 现在解析body获取所有的model的url
		Capsule::table('url_series')->where('status','completed')->orderBy('id')->chunk(20,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	// 保存文件名
		    	$file = PROJECT_APP_DOWN.'series/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		$temp = file_get_contents($file);
					if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 获取所有的body
						foreach($dom->find('#body option') as $article)
						{
						    // 存储进去所有的&body
						    $temp = [
						    	'url' => $data->url.'&body='.$article->value,
						    	'status' => 'wait',
						    ];
						    $empty = Capsule::table('url_body')
						    	->where('url',$data->url.'&body='.$article->value)
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
							    Capsule::table('url_body')->insert($temp);					    	
						    }
						}
			            // 更改SQL语句
			            Capsule::table('url_series')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'series '.$data->id.'.html'."  analyse successful!\r\n";
			            // 记录成功
			            $LibFile->WriteData($logFile, 4,'series '.$data->id.'.html'.'分析完成！');
					}
		    	}
		    }
		});
	}
}
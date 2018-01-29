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

	// 获取所有的&body
	public static function body()
	{


		// 检测数据库是否存在如果不存在就删除
		if(!Capsule::schema()->hasTable('url_body'))
		{
			Capsule::schema()->create('url_body', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable();
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
				if($v == 'F21')
				{
					break;
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
		foreach ($newarticles as $k => $v)
		{

			$mineload = new mineload();
			$res = $mineload->curldownpage($prefix.'&series='.$v);

			if($res['info']['http_code']=='200')
			{
				// 创建文件夹
				@mkdir(PROJECT_APP_DOWN.'series', 0777, true);
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


	// 获取所有的&Model
	public static function model()
	{

		// model表
		if(!Capsule::schema()->hasTable('url_model'))
		{
			Capsule::schema()->create('url_model', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable();
			    $table->string('status')->nullable();
			});
			echo "table url_model create\r\n";
		}

		// 下载所有的body页面
		Capsule::table('url_body')->where('status','wait')->orderBy('id')->chunk(100,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'body/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!is_file($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curldownpage($data->url);
		    		if($res['info']['http_code']== 200 )
		    		{
						// 创建文件夹
						@mkdir(PROJECT_APP_DOWN.'body', 0777, true);
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            
			            // 更改SQL语句
			            Capsule::table('url_body')
					            ->where('id', $data->id)
					            ->update(['status' =>'completed']);
		    		}
		            // 命令行执行时候不需要经过apache直接输出在窗口
		            echo 'body '.$data->id.'.html'."  download successful!\r\n";
		            // 记录成功
		            $LibFile->WriteData($logFile, 4,$data->id.'.html'.'下载完成！');
		    	}
		    }
		});

		// 现在解析body获取所有的model的url
		Capsule::table('url_body')->where('status','completed')->orderBy('id')->chunk(100,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	// 保存文件名
		    	$file = PROJECT_APP_DOWN.'body/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (is_file($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 获取所有的body
						foreach($dom->find('#model option') as $article)
						{
						    // 存储进去所有的&body
						    $temp = [
						    	'url' => $data->url.'&model='.$article->value,
						    	'status' => 'wait',
						    ];
						    Capsule::table('url_model')->insert($temp);
				            // 更改SQL语句
				            Capsule::table('url_body')
						            ->where('id', $data->id)
						            ->update(['status' =>'readed']);
						}
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'body '.$data->id.'.html'."  analyse successful!\r\n";
			            // 记录成功
			            $LibFile->WriteData($logFile, 4,'body '.$data->id.'.html'.'分析完成！');
					}
		    	}
		    }
		});
	}



	// 获取所有的&market
	public static function market()
	{

		// model表
		if(!Capsule::schema()->hasTable('url_market'))
		{
			Capsule::schema()->create('url_market', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable();
			    $table->string('status')->nullable();
			});
			echo "table url_market create\r\n";
		}

		// 下载所有的model页面
		Capsule::table('url_model')->where('status','wait')->orderBy('id')->chunk(100,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'model/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!is_file($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curldownpage($data->url);
		    		if($res['info']['http_code']== 200 )
		    		{
						// 创建文件夹
						@mkdir(PROJECT_APP_DOWN.'model', 0777, true);
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            
			            // 更改SQL语句
			            Capsule::table('url_model')
					            ->where('id', $data->id)
					            ->update(['status' =>'completed']);
		    		}
		            // 命令行执行时候不需要经过apache直接输出在窗口
		            echo 'model '.$data->id.'.html'."  download successful!\r\n";
		            // 记录成功
		            $LibFile->WriteData($logFile, 4,'model '.$data->id.'.html'.'下载完成！');
		    	}
		    }
		});

		// 现在解析body获取所有的model的url
		Capsule::table('url_model')->where('status','completed')->orderBy('id')->chunk(100,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	// 保存文件名
		    	$file = PROJECT_APP_DOWN.'model/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (is_file($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 获取所有的body
						foreach($dom->find('#market option') as $article)
						{
						    // 存储进去所有的&body
						    $temp = [
						    	'url' => $data->url.'&market='.$article->value,
						    	'status' => 'wait',
						    ];
						    Capsule::table('url_market')->insert($temp);
				            // 更改SQL语句
				            Capsule::table('url_model')
						            ->where('id', $data->id)
						            ->update(['status' =>'readed']);
						}
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'body '.$data->id.'.html'."  analyse successful!\r\n";
			            // 记录成功
			            $LibFile->WriteData($logFile, 4,'body '.$data->id.'.html'.'分析完成！');
					}
		    	}
		    }
		});
	}



	// 获取所有的&prod
	public static function prod()
	{

		// model表
		if(!Capsule::schema()->hasTable('url_prod'))
		{
			Capsule::schema()->create('url_prod', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable();
			    $table->string('status')->nullable();
			});
			echo "table url_prod create\r\n";
		}

		// 下载所有的body页面
		Capsule::table('url_market')->where('status','wait')->orderBy('id')->chunk(100,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'market/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!is_file($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curldownpage($data->url);
		    		if($res['info']['http_code']== 200 )
		    		{
						// 创建文件夹
						@mkdir(PROJECT_APP_DOWN.'market', 0777, true);
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            
			            // 更改SQL语句
			            Capsule::table('url_market')
					            ->where('id', $data->id)
					            ->update(['status' =>'completed']);
		    		}
		            // 命令行执行时候不需要经过apache直接输出在窗口
		            echo 'market '.$data->id.'.html'."  download successful!\r\n";
		            // 记录成功
		            $LibFile->WriteData($logFile, 4,'market '.$data->id.'.html'.'下载完成！');
		    	}
		    }
		});

		// 现在解析body获取所有的model的url
		Capsule::table('url_market')->where('status','completed')->orderBy('id')->chunk(100,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	// 保存文件名
		    	$file = PROJECT_APP_DOWN.'market/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (is_file($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 获取所有的body
						foreach($dom->find('#prod option') as $article)
						{
						    // 存储进去所有的&body
						    $temp = [
						    	'url' => $data->url.'&prod='.$article->value,
						    	'status' => 'wait',
						    ];
						    Capsule::table('url_prod')->insert($temp);
				            // 更改SQL语句
				            Capsule::table('url_market')
						            ->where('id', $data->id)
						            ->update(['status' =>'readed']);
						}
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'market '.$data->id.'.html'."  analyse successful!\r\n";
			            // 记录成功
			            $LibFile->WriteData($logFile, 4,'market '.$data->id.'.html'.'分析完成！');
					}
		    	}
		    }
		});
	}




	// 获取所有的&engine
	public static function engine()
	{

		// engine表
		if(!Capsule::schema()->hasTable('url_engine'))
		{
			Capsule::schema()->create('url_engine', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable();
			    $table->string('status')->nullable();
			});
			echo "table url_engine create\r\n";
		}

		// 下载所有的prod页面
		Capsule::table('url_prod')->where('status','wait')->orderBy('id')->chunk(100,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'engine/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!is_file($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curldownpage($data->url);
		    		if($res['info']['http_code']== 200 )
		    		{
						// 创建文件夹
						@mkdir(PROJECT_APP_DOWN.'prod', 0777, true);
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            
			            // 更改SQL语句
			            Capsule::table('url_prod')
					            ->where('id', $data->id)
					            ->update(['status' =>'completed']);
		    		}
		            // 命令行执行时候不需要经过apache直接输出在窗口
		            echo 'prod '.$data->id.'.html'."  download successful!\r\n";
		            // 记录成功
		            $LibFile->WriteData($logFile, 4,'prod '.$data->id.'.html'.'下载完成！');
		    	}
		    }
		});

		// 现在解析body获取所有的model的url
		Capsule::table('url_prod')->where('status','completed')->orderBy('id')->chunk(100,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	// 保存文件名
		    	$file = PROJECT_APP_DOWN.'engine/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (is_file($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 获取所有的body
						foreach($dom->find('#engine option') as $article)
						{
						    // 存储进去所有的&body
						    $temp = [
						    	'url' => $data->url.'&engine='.$article->value,
						    	'status' => 'wait',
						    ];
						    Capsule::table('url_engine')->insert($temp);
				            // 更改SQL语句
				            Capsule::table('url_prod')
						            ->where('id', $data->id)
						            ->update(['status' =>'readed']);
						}
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'prod '.$data->id.'.html'."  analyse successful!\r\n";
			            // 记录成功
			            $LibFile->WriteData($logFile, 4,'prod '.$data->id.'.html'.'分析完成！');
					}
		    	}
		    }
		});
	}




	// 获取所有的&steering
	public static function steering()
	{

		// engine表
		if(!Capsule::schema()->hasTable('url_steering'))
		{
			Capsule::schema()->create('url_steering', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable();
			    $table->string('status')->nullable();
			});
			echo "table url_steering create\r\n";
		}

		// 下载所有的engine页面
		Capsule::table('url_engine')->where('status','wait')->orderBy('id')->chunk(100,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'engine/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!is_file($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curldownpage($data->url);
		    		if($res['info']['http_code']== 200 )
		    		{
						// 创建文件夹
						@mkdir(PROJECT_APP_DOWN.'engine', 0777, true);
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            
			            // 更改SQL语句
			            Capsule::table('url_engine')
					            ->where('id', $data->id)
					            ->update(['status' =>'completed']);
		    		}
		            // 命令行执行时候不需要经过apache直接输出在窗口
		            echo 'engine '.$data->id.'.html'."  download successful!\r\n";
		            // 记录成功
		            $LibFile->WriteData($logFile, 4,'engine '.$data->id.'.html'.'下载完成！');
		    	}
		    }
		});

		// 现在解析body获取所有的model的url
		Capsule::table('url_engine')->where('status','completed')->orderBy('id')->chunk(100,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	// 保存文件名
		    	$file = PROJECT_APP_DOWN.'steering/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (is_file($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 获取所有的body
						foreach($dom->find('#steering option') as $article)
						{
						    // 存储进去所有的&body
						    $temp = [
						    	'url' => $data->url.'&steering='.$article->value,
						    	'status' => 'wait',
						    ];
						    Capsule::table('url_steering')->insert($temp);
				            // 更改SQL语句
				            Capsule::table('url_engine')
						            ->where('id', $data->id)
						            ->update(['status' =>'readed']);
						}
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'engine '.$data->id.'.html'."  analyse successful!\r\n";
			            // 记录成功
			            $LibFile->WriteData($logFile, 4,'engine '.$data->id.'.html'.'分析完成！');
					}
		    	}
		    }
		});
	}


	// 获取所有的原生数据
	public static function rawdata()
	{

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
			});
			echo "table rawdata create\r\n";
		}



		// 下载所有的sterring页面
		Capsule::table('url_steering')->where('status','wait')->orderBy('id')->chunk(100,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'sterring/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!is_file($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curldownpage($data->url);
		    		if($res['info']['http_code']== 200 )
		    		{
						// 创建文件夹
						@mkdir(PROJECT_APP_DOWN.'sterring', 0777, true);
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            
			            // 更改SQL语句
			            Capsule::table('url_steering')
					            ->where('id', $data->id)
					            ->update(['status' =>'completed']);
		    		}
		            // 命令行执行时候不需要经过apache直接输出在窗口
		            echo 'sterring '.$data->id.'.html'."  download successful!\r\n";
		            // 记录成功
		            $LibFile->WriteData($logFile, 4,'sterring '.$data->id.'.html'.'下载完成！');
		    	}
		    }
		});

		// 解析sterring
		Capsule::table('url_steering')->where('status','completed')->orderBy('id')->chunk(100,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	// 保存文件名
		    	$file = PROJECT_APP_DOWN.'steering/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (is_file($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						$code = $dom->find(".searchResults .column")->plaintext;

						// 获取所有的body
						$temp = [
							'code'=>$code
						];

						$url = str_replace('http://www.realoem.com/bmw/enUS/select?product=P&archive=0&','', $data->url);

						$url = explode('&',$url);

						$newUrl = array();
						foreach ($url as $k => $v)
						{
							$v = explode('=', $v);
							$arr = [current($v) => $v[1]];
							$newUrl[] = $arr;
						}
						$temp = array_merge($temp,$newUrl);

						Capsule::table('rawdata')->insert($temp);

			            Capsule::table('url_sterring')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'steering '.$data->id.'.html'."  analyse successful!\r\n";
			            // 记录成功
			            $LibFile->WriteData($logFile, 4,'steering '.$data->id.'.html'.'分析完成！');
					}
		    	}
		    }
		});


		

	}



}
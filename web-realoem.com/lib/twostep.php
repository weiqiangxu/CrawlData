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
class twostep{

	// 获取所有的&Model
	public static function model()
	{
		// 下载所有的body页面
		Capsule::table('url_body')->where('status','wait')->orderBy('id')->chunk(2,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'body', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'body/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!file_exists($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curldownpage($data->url);
		    		if($res['info']['http_code']== 200 )
		    		{
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            
		    		}
		            // 命令行执行时候不需要经过apache直接输出在窗口
		            echo 'body '.$data->id.'.html'."  download successful!\r\n";
		            // 记录成功
		            $LibFile->WriteData($logFile, 4,$data->id.'.html'.'下载完成！');
		    	}
	            // 更改SQL语句
	            Capsule::table('url_body')
			            ->where('id', $data->id)
			            ->update(['status' =>'completed']);
		    }
		});
		// 现在解析body获取所有的model的url
		Capsule::table('url_body')->where('status','completed')->orderBy('id')->chunk(2,function($datas){
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
		    	if (file_exists($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 获取所有的body
						foreach($dom->find('#model option') as $article)
						{
						    // 存储进去所有的&body
						    $temp = [
						    	'url' => $data->url.'&model='.str_replace(" ","+",$article->value),
						    	'status' => 'wait',
						    ];
						    $empty = Capsule::table('url_model')
						    	->where('url',$data->url.'&model='.str_replace(" ","+",$article->value))
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
							    Capsule::table('url_model')->insert($temp);					    	
						    }
						}
			            // 更改SQL语句
			            Capsule::table('url_body')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
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
		// 下载所有的model页面
		Capsule::table('url_model')->where('status','wait')->orderBy('id')->chunk(2,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'model', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'model/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!file_exists($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curldownpage($data->url);
		    		if($res['info']['http_code']== 200 )
		    		{
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            
		    		}
		            // 命令行执行时候不需要经过apache直接输出在窗口
		            echo 'model '.$data->id.'.html'."  download successful!\r\n";
		            // 记录成功
		            $LibFile->WriteData($logFile, 4,'model '.$data->id.'.html'.'下载完成！');
		    	}
		    	if(file_exists($file))
		    	{
		            // 更改SQL语句
		            Capsule::table('url_model')
				            ->where('id', $data->id)
				            ->update(['status' =>'completed']);
		    	}
		    }
		});

		// 现在解析body获取所有的model的url
		Capsule::table('url_model')->where('status','completed')->orderBy('id')->chunk(2,function($datas){
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
		    	if (file_exists($file))
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
						    $empty = Capsule::table('url_market')
						    	->where('url',$data->url.'&market='.$article->value)
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
					    		Capsule::table('url_market')->insert($temp);
						    }						
						}
			            // 更改SQL语句
			            Capsule::table('url_model')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'model '.$data->id.'.html'."  analyse successful!\r\n";
			            // 记录成功
			            $LibFile->WriteData($logFile, 4,'model '.$data->id.'.html'.'分析完成！');
					}
		    	}
		    }
		});
	}
	// 获取所有的&prod
	public static function prod()
	{
		// 下载所有的market页面
		Capsule::table('url_market')->where('status','wait')->orderBy('id')->chunk(2,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'market', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'market/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!file_exists($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curldownpage($data->url);
		    		if($res['info']['http_code']== 200 )
		    		{
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            
		    		}
		            // 命令行执行时候不需要经过apache直接输出在窗口
		            echo 'market '.$data->id.'.html'."  download successful!\r\n";
		            // 记录成功
		            $LibFile->WriteData($logFile, 4,'market '.$data->id.'.html'.'下载完成！');
		    	}
		    	if(file_exists($file))
		    	{
		            // 更改SQL语句
		            Capsule::table('url_market')
				            ->where('id', $data->id)
				            ->update(['status' =>'completed']);
			    }
		    }
		});

		// 现在解析body获取所有的model的url
		Capsule::table('url_market')->where('status','completed')->orderBy('id')->chunk(2,function($datas){
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
		    	if (file_exists($file))
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

						    $empty = Capsule::table('url_prod')
						    	->where('url',$data->url.'&prod='.$article->value)
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
					    		Capsule::table('url_prod')->insert($temp);
						    }
						}
			            // 更改SQL语句
			            Capsule::table('url_market')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
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
		// 下载所有的prod页面
		Capsule::table('url_prod')->where('status','wait')->orderBy('id')->chunk(2,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'prod', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'prod/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!file_exists($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curldownpage($data->url);
		    		if($res['info']['http_code']== 200 )
		    		{
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            
		    		}
		            // 命令行执行时候不需要经过apache直接输出在窗口
		            echo 'prod '.$data->id.'.html'."  download successful!\r\n";
		            // 记录成功
		            $LibFile->WriteData($logFile, 4,'prod '.$data->id.'.html'.'下载完成！');
		    	}
		    	if(file_exists($file))
		    	{		    	
		            // 更改SQL语句
		            Capsule::table('url_prod')
				            ->where('id', $data->id)
				            ->update(['status' =>'completed']);
				}
		    }
		});
		// 现在解析body获取所有的model的url
		Capsule::table('url_prod')->where('status','completed')->orderBy('id')->chunk(2,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	// 保存文件名
		    	$file = PROJECT_APP_DOWN.'prod/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
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

							$empty = Capsule::table('url_engine')
						    	->where('url',$data->url.'&engine='.$article->value)
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
					    		Capsule::table('url_engine')->insert($temp);
						    }
						}
			            // 更改SQL语句
			            Capsule::table('url_prod')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
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
		// 下载所有的engine页面
		Capsule::table('url_engine')->where('status','wait')->orderBy('id')->chunk(2,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'engine', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'engine/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!file_exists($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curldownpage($data->url);
		    		if($res['info']['http_code']== 200 )
		    		{
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            
		    		}
		            // 命令行执行时候不需要经过apache直接输出在窗口
		            echo 'engine '.$data->id.'.html'."  download successful!\r\n";
		            // 记录成功
		            $LibFile->WriteData($logFile, 4,'engine '.$data->id.'.html'.'下载完成！');
		    	}
		    	if(file_exists($file))
		    	{	
		            // 更改SQL语句
		            Capsule::table('url_engine')
				            ->where('id', $data->id)
				            ->update(['status' =>'completed']);
			    }
		    }
		});

		// 现在解析body获取所有的model的url
		Capsule::table('url_engine')->where('status','completed')->orderBy('id')->chunk(2,function($datas){
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
		    	if (file_exists($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						if($dom->find('#steering option'))
						{
							// 获取所有的body
							foreach($dom->find('#steering option') as $article)
							{
								// 获取所有的
							    // 存储进去所有的&body
							    $temp = [
							    	'url' => $data->url.'&steering='.$article->value,
							    	'status' => 'wait',
							    ];
								$empty = Capsule::table('url_steering')
							    	->where('url',$data->url.'&steering='.$article->value)
							    	->get()
							    	->isEmpty();
							    if($empty)
							    {
						    		Capsule::table('url_steering')->insert($temp);
							    }
					            // 更改SQL语句
							}
				            Capsule::table('url_engine')
						            ->where('id', $data->id)
						            ->update(['status' =>'readed']);
						}
						else
						{
							$temp = [
							    	'url' => $data->url,
							    	'status' => 'wait',
							    ];
							$empty = Capsule::table('url_steering')
						    	->where('url',$data->url)
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
					    		Capsule::table('url_steering')->insert($temp);
						    }
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

	// 获取所有的&最后级别页面
	public static function rawdata()
	{
		// 下载所有的最终级别页面
		Capsule::table('url_steering')->where('status','wait')->orderBy('id')->chunk(2,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'rawdata', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'rawdata/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!file_exists($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curldownpage($data->url);
		    		if($res['info']['http_code']== 200 )
		    		{
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            
		    		}
		            // 命令行执行时候不需要经过apache直接输出在窗口
		            echo 'rawdata '.$data->id.'.html'."  download successful!\r\n";
		            // 记录成功
		            $LibFile->WriteData($logFile, 4,'rawdata '.$data->id.'.html'.'下载完成！');
		    	}

		    	if(file_exists($file))
		    	{
		            // 更改SQL语句
		            Capsule::table('url_steering')
				            ->where('id', $data->id)
				            ->update(['status' =>'completed']);
			    }
		    }
		});

		// 现在解析body获取所有的model的url
		Capsule::table('url_steering')->where('status','readed')->orderBy('id')->chunk(2,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'onestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	// 保存文件名
		    	$file = PROJECT_APP_DOWN.'rawdata/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		$temp = file_get_contents($file);

					
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{

						$temp = array();
						// series
						$series = trim(str_replace("?"," ", $dom->find("#series [selected=selected]",0)->innertext));
						$series = str_replace("&mdash;", "-", $series);
						$temp['series'] = str_replace("&emsp;"," ", $series); 
						// body
						$temp['body'] = trim($dom->find("#body [selected=selected]",0)->innertext);
						// model
						$temp['model'] = trim($dom->find("#model [selected=selected]",0)->innertext);
						// market
						$temp['market'] = trim($dom->find("#market [selected=selected]",0)->innertext);
						// prod
						$temp['prod'] = trim($dom->find("#prod [selected=selected]",0)->innertext);
						// engine
						$temp['engine'] = trim($dom->find("#engine [selected=selected]",0)->innertext);
						// steering
						if($dom->find("#steering [selected=selected]",0))
						{
							$temp['steering'] = trim($dom->find("#steering [selected=selected]",0)->innertext);
						}


						if($dom->find('.searchResults',0)->first_child())
						{
							// 获取所有的body
							$code = $dom->find('.searchResults',0)->first_child()->innertext;
							$code = trim(str_replace("You Have Selected: ", "", $code));
							$code = explode("Type Code:",$code);
						}
						else
						{
							$code = "";
							// 命令行执行时候不需要经过apache直接输出在窗口
				            echo 'url_steering '.$data->id.'.html'."  code  not fund !\r\n";
				            // 记录成功
				            $LibFile->WriteData($logFile, 4,'url_steering '.$data->id.'.html'.'code没找到！');
						}
			            // 入库rawdata
			            
			            $temp['name'] = trim(current($code));
			            $temp['code'] = trim($code[1]);
			            $temp['url'] = $data->url;


			            // 入库数据
						$empty = Capsule::table('rawdata')
					    	->where('url',$data->url)
					    	->get()
					    	->isEmpty();
					    if($empty)
					    {
			          		Capsule::table('rawdata')->insert($temp); 
					    }

					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'engine '.$data->id.'.html'."  analyse successful!\r\n";
			            // 记录成功
			            $LibFile->WriteData($logFile, 4,'engine '.$data->id.'.html'.'分析完成！');
					}
					else
					{
						// 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'url_steering '.$data->id.'.html'." not fund !\r\n";
			            // 记录成功
			            $LibFile->WriteData($logFile, 4,'url_steering '.$data->id.'.html'.' 没找到！');
					}
					// 更改SQL语句
		            Capsule::table('url_steering')
				            ->where('id', $data->id)
				            ->update(['status' =>'readed']);
		    	}
		    }
		});
		echo " analyse is completed !\r\n";
	}

}
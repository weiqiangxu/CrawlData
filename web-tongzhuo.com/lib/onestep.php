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
			    $table->text('title')->nullable();
			    $table->text('des')->nullable();
			    $table->string('qualification')->nullable();
			    $table->text('model')->nullable();
			    $table->string('Home/EU')->nullable();
			    $table->string('Overseas')->nullable();
			    $table->string('web_name')->nullable();
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
	

	// 剑桥大学
	public static function jianqiao()
	{
		$res = json_decode(file_get_contents(__DIR__.'\jianqiao.json'), true);
		$res = $res['data']; 
		print_r($res);
	}

	// 牛津大学
	public static function niujin()
	{
		$web = 'https://www.ox.ac.uk/admissions/graduate/courses/courses-a-z-listing?wssl=1#';
		// 解析页面
		$client = new Client();
		$response = $client->get($web,['verify' => true]);
		
		@mkdir(PROJECT_APP_DOWN, 0777, true);
		// 保存首页
		file_put_contents(PROJECT_APP_DOWN.'niujin.html', $response->getBody());
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.'niujin.html')))
		{
			foreach($dom->find('.view-content .course-listing') as $div)
			{
				$url = 'https://www.ox.ac.uk/'.$div->find('a',0)->href;
				$des = trim($div->find('.course-department',0)->plaintext).' '.trim($div->find(".course-mode-of-study",0)->plaintext).' '.trim($div->find('.course-duration',0)->plaintext);
			    // 存储
			    $temp = [
			    	'url' => $url,
			    	'status' => 'wait',
			    	'md5_url' => md5($url),
			    	'title' => $div->find('.course-title',0)->plaintext,
			    	'des' => $des
			    ];

			    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
			    // 入库
			    if($empty) Capsule::table('info')->insert($temp);
			}
			echo 'niujin analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}

	// 帝国理工
	public static function diguo()
	{
		$web_name = 'diguo';
		if(!is_file(PROJECT_APP_DOWN.$web_name.'.html'))
		{
			$web = 'http://www.imperial.ac.uk/study/pg/courses/';
			// 解析页面
			$client = new Client();
			$response = $client->get($web);
			@mkdir(PROJECT_APP_DOWN, 0777, true);
			echo '文件不存在====>获取文件!';
			// 保存首页
			file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		}
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		{
			$base_url='http://www.imperial.ac.uk';
			foreach($dom->find('.course') as $li)
			{
				// 第二层
				if(strpos($li->find('div',2)->plaintext,'full-time'))
				{
					$url = $li->find('a',0)->href;
					$title = $li->find('.title',0)->plaintext;
				    $temp = [
				    	'url' => $base_url.$url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'des' => $li->find('.dept',0)->plaintext,
				    	'qualification'=>str_replace('Qualification/s:', '', $li->find('.type',1)->plaintext),
				    	'model'=>str_replace('Mode of study:', '', $li->find('.type',2)->plaintext),
				    	'web_name'=>$web_name
				    ];
				    Capsule::table('info')->insert($temp);
				    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    // 入库
				    // if($empty) Capsule::table('info')->insert($temp);
				    echo $title.' has Done!'.PHP_EOL;
					
				}
			}
			echo 'diguo analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}

	// lse
	public static function lse()
	{
		$web_name = 'lse';
		if(!is_file(PROJECT_APP_DOWN.$web_name.'.html'))
		{
			$web = 'http://www.lse.ac.uk/study-at-lse/Graduate/Available-programmes';
			// 解析页面
			$client = new Client();
			$response = $client->get($web);
			@mkdir(PROJECT_APP_DOWN, 0777, true);
			echo '文件不存在====>获取文件!';
			// 保存首页
			file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		}
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		{
			$base_url='http://www.lse.ac.uk';
			foreach($dom->find('.A-M tr') as $tr)
			{
					$url = $base_url.$tr->find('a',0)->href;
					$title = $tr->find('p',0)->plaintext;
				    $temp = [
				    	'url' => $url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'des' => 'A-M - Taught masters / LLM',
				    	'Home/EU'=>$tr->find('p',1)->plaintext,
				    	'Overseas'=>$tr->find('p',2)->plaintext,
				    	'web_name'=>$web_name
				    ];
				    Capsule::table('info')->insert($temp);
				    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    // if($empty) Capsule::table('info')->insert($temp);
				    echo $title.' has Done!'.PHP_EOL;
					
			}
			foreach($dom->find('.N-Z tr') as $tr)
			{
					$url = $base_url.$tr->find('a',0)->href;
					$title = $tr->find('p',0)->plaintext;
				    $temp = [
				    	'url' => $url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'des' => 'N-Z - Taught masters, including MPA',
				    	'Home/EU'=>$tr->find('p',1)->plaintext,
				    	'Overseas'=>$tr->find('p',2)->plaintext,
				    	'web_name'=>$web_name
				    ]; 
				     Capsule::table('info')->insert($temp);
				    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    // if($empty) Capsule::table('info')->insert($temp);
				    echo $title.' has Done!'.PHP_EOL;
			}
			foreach($dom->find('.Research tr') as $tr)
			{
					$url = $tr->find('a',0)->href?$base_url.$tr->find('a',0)->href:'';
					$title = $tr->find('p',0)->plaintext;
				    $temp = [
				    	'url' => $url,
				    	'status' => 'wait',
				    	'md5_url' => $url?md5($url):'',
				    	'title' => $title,
				    	'des' => 'Research',
				    	'Home/EU'=>$tr->find('p',1)->plaintext,
				    	'Overseas'=>$tr->find('p',2)->plaintext,
				    	'web_name'=>$web_name
				    ]; 
				     Capsule::table('info')->insert($temp);
				    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    // if($empty) Capsule::table('info')->insert($temp);
				    echo $title.' has Done!'.PHP_EOL;
			}
			echo 'lse analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}

	// ucl
	public static function ucl()
	{
		$arr = ['taught'=>'http://www.ucl.ac.uk/prospective-students/graduate/taught/degrees','research'=>'http://www.ucl.ac.uk/prospective-students/graduate/research/degrees'];
		$web_name = 'ucl';
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// $web = 'http://www.lse.ac.uk/study-at-lse/Graduate/Available-programmes';
				// 解析页面
				$client = new Client();
					$response = $client->get($value);
					@mkdir(PROJECT_APP_DOWN, 0777, true);
					echo '文件不存在====>获取文件!';
					// 保存首页
					file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
		}
		// 创建dom对象
		foreach ($arr as $key => $value) 
		{
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				// $base_url='www.ucl.ac.uk';
				foreach($dom->find('tr') as $tr)
				{
					if($url = $tr->find('a',0)->href)
					{
						$title = $tr->find('a',0)->plaintext;
					    $temp = [
					    	'url' => $url,
					    	'status' => 'wait',
					    	'md5_url' => md5($url),
					    	'title' => $title,
					    	'des' => $key,
					    	'qualification'=>$tr->find('td',1)->plaintext,
					    	'model'=>$tr->find('td',2)->plaintext,
					    	'web_name'=>$web_name
					    ];
					    // 入库
					    Capsule::table('info')->insert($temp);
					    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
					    // if($empty) Capsule::table('info')->insert($temp);
					    echo $title.' has Done!';
					}
						
				}
				echo 'ucl analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
	}

	// Edinburgh
	public static function Edinburgh()
	{
		$web_name = 'Edinburgh';
		if(!is_file(PROJECT_APP_DOWN.$web_name.'.html'))
		{
			$web = 'https://www.ed.ac.uk/studying/postgraduate/degrees/index.php?r=site%2Fsearch&pgSearch=&yt0=&moa=a';
			// 解析页面
			$client = new Client();
			$response = $client->get($web);
			@mkdir(PROJECT_APP_DOWN, 0777, true);
			echo '文件不存在====>获取文件!';
			// 保存首页
			file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		}
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		{
			$base_url='https://www.ed.ac.uk';
			foreach($dom->find('.list-group-item') as $a)
			{
				if(!strpos($a->plaintext,'online'))
				{
					$url = $a->href;
					$que =  $a->find('span',0)->plaintext;
					$title = str_replace($que, '',$a->plaintext);
				    $temp = [
				    	'url' => $base_url.$url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'des' => '',
				    	'qualification'=>$que,
				    	'model'=>'',
				    	'web_name'=>$web_name
				    ];
				    Capsule::table('info')->insert($temp);
				    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    // 入库
				    // if($empty) Capsule::table('info')->insert($temp);
				    echo $title.' has Done!'.PHP_EOL;
					
				}
			}
			// echo 'diguo analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}

	// KCL
	public static function KCL()
	{
		$web_name = 'KCL';
		$base_url = 'https://www.kcl.ac.uk';
		for ($i=1; $i < 18; $i++) 
		{ 
			$res = json_decode(file_get_contents(PROJECT_APP_DOWN.$web_name.'/'.$i.'.php'), true);
			foreach ($res['items'] as $value) 
			{
					$model = '';
					if(!empty($value['studyMode1']))
					{
						foreach ($value['studyMode1'] as $v) 
						{
							$model .= '/ '.$v['name'];
						}
					}
					$url = $value['searchData']['url'];
					$title = $value['title'];
				    $temp = [
				    	'url' => $base_url.$url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'des' => $value['summary'],
				    	'qualification'=>$value['qualifications'],
				    	'model'=>trim('/ ',$model),
				    	'web_name'=>$web_name
				    ];
				    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    // if($empty) Capsule::table('info')->insert($temp);
				    Capsule::table('info')->insert($temp);
				    echo $title.' has Done!'.PHP_EOL;
			}
		}
		echo 'kcl analyse completed!'.PHP_EOL;
	}

	// Manchester
	public static function Manchester()
	{
		$base_url = ['courses'=>'http://www.manchester.ac.uk/study/masters/courses/list/','programmes'=>'http://www.manchester.ac.uk/study/postgraduate-research/programmes/list/'];
		$arr = ['courses'=>'http://www.manchester.ac.uk/study/masters/courses/list/','programmes'=>'http://www.manchester.ac.uk/study/postgraduate-research/programmes/list/'];
		$web_name = 'Manchester';
		// foreach ($arr as $key=> $value) 
		// {
		// 	if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
		// 	{
		// 		// 解析页面
		// 		$client = new Client();
		// 			$response = $client->get($value);
		// 			@mkdir(PROJECT_APP_DOWN, 0777, true);
		// 			echo '文件不存在====>获取文件!';
		// 			// 保存首页
		// 			file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
		// 	}
		// }
		// 创建dom对象
		foreach ($base_url as $key => $value) 
		{
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				// $base_url='www.ucl.ac.uk';
				foreach($dom->find('li') as $tr)
				{
					$url = $value.$tr->find('a',0)->href;
					$qua = $tr->find('.degree',0)->plaintext;
					$title = str_replace($qua, '', $tr->find('a',0)->plaintext);
				    $temp = [
				    	'url' => $url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'des' => $key,
				    	'qualification'=>$qua,
				    	'model'=>$tr->find('.duration',0)->plaintext,
				    	'web_name'=>$web_name
				    ];
				    // 入库
				    Capsule::table('info')->insert($temp);
				    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    // if($empty) Capsule::table('info')->insert($temp);
				    echo $title.' has Done!'.PHP_EOL;
						
				}
				echo 'ucl analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
	}
	
	// Bristol
	public static function Bristol()
	{
		$web_name = 'Bristol';
		// if(!is_file(PROJECT_APP_DOWN.$web_name.'.html'))
		// {
		// 	$web = 'http://www.bris.ac.uk/study/postgraduate/search/';
		// 	// 解析页面
		// 	$client = new Client();
		// 	$response = $client->get($web);
		// 	@mkdir(PROJECT_APP_DOWN, 0777, true);
		// 	echo '文件不存在====>获取文件!';
		// 	// 保存首页
		// 	file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		// }
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		{
			$base_url='http://www.bris.ac.uk/study/postgraduate/search/';
			foreach($dom->find('li') as $li)
			{
				$url = $li->find('a',0)->href;
				$title = $li->find('a',0)->plaintext;
			    $temp = [
			    	'url' => $base_url.$url,
			    	'status' => 'wait',
			    	'md5_url' => md5($url),
			    	'title' => $title,
			    	'des' => $li->find('.prog-new',0)?$li->find('.prog-new',0)->plaintext:'',
			    	'qualification'=>'',
			    	'model'=>$li->find('.prog-type',0)->plaintext,
			    	'web_name'=>$web_name
			    ];
			    Capsule::table('info')->insert($temp);
			    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
			    // 入库
			    // if($empty) Capsule::table('info')->insert($temp);
			    echo $title.' has Done!'.PHP_EOL;
			}
			echo 'diguo analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}

	// Glasgow
	public static function Glasgow()
	{
		$arr = ['taught'=>'https://www.gla.ac.uk/postgraduate/taught/','research'=>'https://www.gla.ac.uk/research/opportunities/'];
		$base_url = 'https://www.gla.ac.uk';
		$web_name = 'Glasgow';
		// foreach ($arr as $key=> $value) 
		// {
		// 	if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
		// 	{
		// 		// 解析页面
		// 		$client = new Client();
		// 			$response = $client->get($value);
		// 			@mkdir(PROJECT_APP_DOWN, 0777, true);
		// 			echo '文件不存在====>获取文件!';
		// 			// 保存首页
		// 			file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
		// 	}
		// }
		// 创建dom对象
		foreach ($arr as $key => $value) 
		{
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				// $base_url='www.ucl.ac.uk';
				foreach($dom->find('li') as $tr)
				{
					$url = $base_url.$tr->find('a',0)->href;
					$title = $tr->find('a',0)->plaintext;
				    $temp = [
				    	'url' => $url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'des' => $key,
				    	'qualification'=>'',
				    	'model'=>'',
				    	'web_name'=>$web_name
				    ];
				    // 入库
				    Capsule::table('info')->insert($temp);
				    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    // if($empty) Capsule::table('info')->insert($temp);
				    echo $title.' has Done!'.PHP_EOL;
						
				}
				echo 'ucl analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
	}

	// Warwick
	public static function Warwick()
	{
		$web_name = 'Warwick';
		// if(!is_file(PROJECT_APP_DOWN.$web_name.'.html'))
		// {
			$web = 'https://warwick.ac.uk/study/postgraduate/courses-2018/';
		// 	// 解析页面
		// 	$client = new Client();
		// 	$response = $client->get($web);
		// 	@mkdir(PROJECT_APP_DOWN, 0777, true);
		// 	echo '文件不存在====>获取文件!';
		// 	// 保存首页
		// 	file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		// }
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.'Warwick.html')))
		{
			// var_dump($dom);
			$base_url='http://www.bris.ac.uk/study/postgraduate/search/';
			foreach($dom->find('.sb-glossary-terms a') as $li)
			{
				if(isset($li->href))
				{
					$url = $li->href;
					$title = $li->plaintext;
				    $temp = [
				    	'url' => $url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'des' => $web,
				    	'qualification'=>'',
				    	'model'=>'',
				    	'web_name'=>$web_name
				    ];
				    Capsule::table('info')->insert($temp);
				    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    // 入库
				    // if($empty) Capsule::table('info')->insert($temp);
				    echo $title.' has Done!'.PHP_EOL;
					
				}
			}
			$arr=[];
			foreach($dom->find('.boxstyle_box5 h5') as $key=>$li)
			{
				if(isset($li->find('a',0)->href))
				{
					$url = $li->find('a',0)->href;
					if(!preg_match('/(http:\/\/)|(https:\/\/)/i', $url))
					{
						$url = $web.$url;
					}
					$title = $li->find('a',0)->plaintext;
					$arr[$key]['title'] = $title;
					$arr[$key]['url'] = $url;
				}
			}
			for ($i=1; $i < 33; $i++) 
			{ 
				$dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.'warwick_right/'.$i.'.html'));
				 foreach($dom->find('table') as $li)
				{
					if(@$li->find('tr',0)->find('h3',0)->plaintext)
					{
						$url = $arr[$i-1]['url'];
						$title = $li->find('tr',0)->find('h3',0)->plaintext;
					    $temp = [
					    	'url' => $url,
					    	'status' => 'wait',
					    	'md5_url' => md5($url),
					    	'title' => $title,
					    	'des' => $li->find('tr',1)->find('td',1)->find('p',1)->plaintext,
					    	'qualification'=>$li->find('tr',1)->find('td',2)->find('p',1)->plaintext,
					    	'model'=>$li->find('tr',2)->find('td',0)->find('p',1)->plaintext,
					    	// 'Home/EU'=>$li->find('tr',2)->find('td',1)->find('p',0)->plaintext.'/'.$li->find('tr',2)->find('td',1)->find('p',0)->plaintext.$li->find('tr',2)->find('td',2)->plaintext,
					    	// 'Overseas'=>
					    	'web_name'=>$web_name
					    ];
					    Capsule::table('info')->insert($temp);
					    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
					    // 入库
					    // if($empty) Capsule::table('info')->insert($temp);
					    echo $title.' has Done!'.PHP_EOL;
						
					}
				}
				echo $i.PHP_EOL;
				$dom-> clear(); 
			}
			echo 'diguo analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}

	// Sheffield
	public static function Durham()
	{
		$web_name = 'Durham';
		// if(!is_file(PROJECT_APP_DOWN.$web_name.'.html'))
		// {
		// 	$web = 'https://www.dur.ac.uk/courses/all/#indexA';
		// 	// 解析页面
		// 	$client = new Client();
		// 	$response = $client->get($web);
		// 	@mkdir(PROJECT_APP_DOWN, 0777, true);
		// 	echo '文件不存在====>获取文件!';
		// 	// 保存首页
		// 	file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		// }
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		{
			$base_url='https://www.dur.ac.uk';
			foreach($dom->find('#content tr') as $li)
			{
				// 第二层
				$class = $li->class;
				if(strpos($class,'PostgraduateTaught') && strpos($class,'2018') && strpos($class,'FT'))
				{
					$td2 = $li->find('td',2)->plaintext;
					if($td2 !='	BA' && $td2 !='BEng' && $td2 !='BSc' && $td2 !='GDip')
					{
						$url = $base_url.$li->find('a',0)->href;
						$title = $li->find('a',0)->plaintext;
					    $temp = [
					    	'url' => $url,
					    	'status' => 'wait',
					    	'md5_url' => md5($url),
					    	'title' => $title,
					    	'des' =>$class ,
					    	'qualification'=>$li->find('td',0)->plaintext,
					    	'model'=>$li->find('td',2)->plaintext,
					    	'web_name'=>$web_name
					    ];
					    Capsule::table('info')->insert($temp);
					    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
					    // 入库
					    // if($empty) Capsule::table('info')->insert($temp);
					    echo $title.' has Done!'.PHP_EOL;
						
					}
					
				}
			}
			foreach($dom->find('.research tr') as $li)
			{
				// 第二层
				
				$url = $li->find('a',0)->href;
				$title = $li->find('a',0)->plaintext;
			    $temp = [
			    	'url' => $url,
			    	'status' => 'wait',
			    	'md5_url' => md5($url),
			    	'title' => $title,
			    	'des' =>$li->find('td',1)->plaintext ,
			    	'qualification'=>$li->find('td',2)->plaintext ,
			    	'model'=>$li->find('td',3)->plaintext,
			    	'Home/EU'=>'research',
			    	'web_name'=>$web_name
			    ];
			    Capsule::table('info')->insert($temp);
			    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
			    // 入库
			    // if($empty) Capsule::table('info')->insert($temp);
			    echo $title.' has Done!'.PHP_EOL;
						
				
			}
			echo 'diguo analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}

	// Sheffield
	public static function Sheffield()
	{
		$web_name = 'Sheffield';
		// if(!is_file(PROJECT_APP_DOWN.$web_name.'.html'))
		// {
		// 	$web = 'https://www.sheffield.ac.uk/postgraduate/taught/courses/all';
		// 	// 解析页面
		// 	$client = new Client();
		// 	$response = $client->get($web);
		// 	@mkdir(PROJECT_APP_DOWN, 0777, true);
		// 	echo '文件不存在====>获取文件!';
		// 	// 保存首页
		// 	file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		// }
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		{
			$base_url='https://www.sheffield.ac.uk';
			foreach($dom->find('p') as $li)
			{
				if($li->find('a',0)->href)
				{
					$url = $base_url.$li->find('a',0)->href;
					$title = $li->find('a',0)->plaintext;
				    $temp = [
				    	'url' => $url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	// 'des' =>$class ,
				    	// 'qualification'=>$li->find('td',0)->plaintext,
				    	// 'model'=>$li->find('td',2)->plaintext,
				    	'web_name'=>$web_name
				    ];
				    Capsule::table('info')->insert($temp);
				    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    // 入库
				    // if($empty) Capsule::table('info')->insert($temp);
				    echo $title.' has Done!'.PHP_EOL;	
				}
			}
			foreach($dom->find('.research tr') as $li)
			{
				// 第二层
				
				$url = $li->find('a',0)->href;
				$title = $li->find('a',0)->plaintext;
			    $temp = [
			    	'url' => $url,
			    	'status' => 'wait',
			    	'md5_url' => md5($url),
			    	'title' => $title,
			    	'des' =>$li->find('td',1)->plaintext ,
			    	'qualification'=>$li->find('td',2)->plaintext ,
			    	'model'=>$li->find('td',3)->plaintext,
			    	'Home/EU'=>'research',
			    	'web_name'=>$web_name
			    ];
			    Capsule::table('info')->insert($temp);
			    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
			    // 入库
			    // if($empty) Capsule::table('info')->insert($temp);
			    echo $title.' has Done!'.PHP_EOL;
						
				
			}
			echo 'diguo analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}

	// Queenmary
	public static function Queenmary()
	{
		$web_name = 'Queenmary';
		for ($i=0; $i < 27; $i++) 
		{ 
			if(!is_file(PROJECT_APP_DOWN.$web_name.'/'.$i.'.html'))
			{
				$web = 'http://search.qmul.ac.uk/s/search.html?collection=queenmary-coursefinder-pg&query=&f.Mode%7CM=full+time&sort=title&start_rank='.$i.'1';
				// 解析页面
				$client = new Client();
				$response = $client->get($web);
				// @mkdir(PROJECT_APP_DOWN, 0777, true);
				echo $i.'.html 文件不存在====>获取文件!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'/'.$i.'.html', $response->getBody());
			}
		}
		// 创建dom对象
		// if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		// {
		// 	$base_url='https://www.sheffield.ac.uk';
		// 	foreach($dom->find('p') as $li)
		// 	{
		// 		if($li->find('a',0)->href)
		// 		{
		// 			$url = $base_url.$li->find('a',0)->href;
		// 			$title = $li->find('a',0)->plaintext;
		// 		    $temp = [
		// 		    	'url' => $url,
		// 		    	'status' => 'wait',
		// 		    	'md5_url' => md5($url),
		// 		    	'title' => $title,
		// 		    	// 'des' =>$class ,
		// 		    	// 'qualification'=>$li->find('td',0)->plaintext,
		// 		    	// 'model'=>$li->find('td',2)->plaintext,
		// 		    	'web_name'=>$web_name
		// 		    ];
		// 		    Capsule::table('info')->insert($temp);
		// 		    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
		// 		    // 入库
		// 		    // if($empty) Capsule::table('info')->insert($temp);
		// 		    echo $title.' has Done!'.PHP_EOL;	
		// 		}
		// 	}
		// 	foreach($dom->find('.research tr') as $li)
		// 	{
		// 		// 第二层
				
		// 		$url = $li->find('a',0)->href;
		// 		$title = $li->find('a',0)->plaintext;
		// 	    $temp = [
		// 	    	'url' => $url,
		// 	    	'status' => 'wait',
		// 	    	'md5_url' => md5($url),
		// 	    	'title' => $title,
		// 	    	'des' =>$li->find('td',1)->plaintext ,
		// 	    	'qualification'=>$li->find('td',2)->plaintext ,
		// 	    	'model'=>$li->find('td',3)->plaintext,
		// 	    	'Home/EU'=>'research',
		// 	    	'web_name'=>$web_name
		// 	    ];
		// 	    Capsule::table('info')->insert($temp);
		// 	    // $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
		// 	    // 入库
		// 	    // if($empty) Capsule::table('info')->insert($temp);
		// 	    echo $title.' has Done!'.PHP_EOL;
						
				
		// 	}
		// 	echo 'diguo analyse completed!'.PHP_EOL;
		// 	// 清理内存防止内存泄漏
		// 	$dom-> clear(); 
		// }
	}
}
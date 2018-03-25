<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;
use Illuminate\Database\Schema\Blueprint;
use GuzzleHttp\Client;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
			    $table->string('md5_url')->nullable();
			    $table->text('url')->nullable()->comment('链接');
			    $table->string('status')->nullable();
			    $table->text('title')->nullable()->comment('标题');
			    $table->text('des')->nullable()->comment('描述');
			    $table->text('qualification')->nullable()->comment('资格');
			    $table->text('model')->nullable()->comment('典型');
			    $table->text('home')->nullable()->comment('家园');
			    $table->text('overseas')->nullable()->comment('海外的');
			    $table->text('code')->nullable('编码');
			    $table->string('code_url')->nullable('编码链接');
			    $table->text('web_name')->nullable('网站名称');
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
		$web_name = 'jianqiao';
		$res = $res['data']; 
		foreach ($res as $key => $value)
		{
		    $temp = [
			    	'url' => $value['prospectus_url'],
			    	'status' => 'wait',
			    	'md5_url' => md5($value['prospectus_url']),
			    	'title' => $value['name'],
			    	'des' => $value['full_name'],
			    	'model'=>$value['taught_research_balance'],
			    	'qualification'=>$value['qualification'],
			    	'home'=>implode('/ ', $value['departments']),
			    	'code'=>$value['code'],
			    	'overseas'=>$value['qualification_type'],
			    	'web_name'=>$web_name
			    ];
		    // 入库
		    $empty = Capsule::table('info')->where('md5_url',md5($value['prospectus_url']))->get()->isEmpty();
		    if($empty) Capsule::table('info')->insert($temp);
		}
		echo 'jianqiao analyse completed!'.PHP_EOL;
	}

	// 牛津大学
	public static function niujin()
	{
		$web_name = 'niujin';
		// 解析页面
		if(!file_exists(PROJECT_APP_DOWN.$web_name.'.html'))
		{
			// 解析页面
			$web = 'https://www.ox.ac.uk/admissions/graduate/courses/courses-a-z-listing?wssl=1#';
			$client = new Client();
			$response = $client->get($web,['verify' => false]);
			@mkdir(PROJECT_APP_DOWN, 0777, true);
			// 保存首页
			file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		}
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
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
			    	'des' => $des,
			    	'web_name'=>$web_name
			    ];
			    // 入库
			    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
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
			echo 'download diguo completed!'.PHP_EOL;
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
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    // 入库
				    if($empty) Capsule::table('info')->insert($temp);
					
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
			echo 'download lse completed!'.PHP_EOL;
			// 保存首页
			file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		}
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		{
			$base_url='http://www.lse.ac.uk';
			foreach($dom->find('.accordion',0)->find('tbody tr') as $tr)
			{
				if($tr->find('a') && $url=$base_url.$tr->find('a',0)->href)
				{
					$title = $tr->find('p',0)->plaintext;
				    $temp = [
				    	'url' => $url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => str_replace('&nbsp;', '',$title),
				    	'des' => 'A-M',
				    	'home'=>$tr->find('p',1)?str_replace('&nbsp;', '',$tr->find('p',1)->plaintext):'',
				    	'overseas'=>$tr->find('p',2)?str_replace('&nbsp;', '',$tr->find('p',2)->plaintext):'',
				    	'web_name'=>$web_name
				    ];
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
				}
					
			}
			foreach($dom->find('.accordion',1)->find('tbody tr') as $tr)
			{
				if($tr->find('a') && $url=$base_url.$tr->find('a',0)->href)
				{
					$title = $tr->find('p',0)->plaintext;
				    $temp = [
				    	'url' => $url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' =>str_replace('&nbsp;', '',$title),
				    	'des' => 'N-Z',
				    	'home'=>$tr->find('p',1)?str_replace('&nbsp;', '',$tr->find('p',1)->plaintext):'',
				    	'overseas'=>$tr->find('p',2)?str_replace('&nbsp;', '',$tr->find('p',2)->plaintext):'',
				    	'web_name'=>$web_name
				    ];
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
				}
			}
			foreach($dom->find('.accordion',5)->find('tbody tr') as $tr)
			{
				if($tr->find('a') && $url=$base_url.$tr->find('a',0)->href)
				{
					$title = $tr->find('p',0)->plaintext;
				    $temp = [
				    	'url' => $url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => str_replace('&nbsp;', '',$title),
				    	'des' => 'research',
				    	'home'=>$tr->find('p',1)?str_replace('&nbsp;', '',$tr->find('p',1)->plaintext):'',
				    	'overseas'=>$tr->find('p',2)?str_replace('&nbsp;', '',$tr->find('p',2)->plaintext):'',
				    	'web_name'=>$web_name
				    ];
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
				}
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
				echo 'download ucl successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				// $base_url='www.ucl.ac.uk';
				foreach($dom->find('#results tr') as $tr)
				{
					if($tr->find('a') && $url = $tr->find('a',0)->href)
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
					    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
					    if($empty) Capsule::table('info')->insert($temp);
					}
						
				}
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
		echo 'ucl analyse completed!'.PHP_EOL;
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
			$response = $client->get($web,['verify'=>false]);
			@mkdir(PROJECT_APP_DOWN, 0777, true);
			echo 'download Edinburgh successful!'.PHP_EOL;
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
				    // 入库
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
					
				}
			}
			// 清理内存防止内存泄漏
			$dom-> clear(); 
			echo 'Edinburgh analyse completed!'.PHP_EOL;
		}
	}

	// KCL 这个是json文件
	public static function KCL()
	{
		$web_name = 'KCL';
		$base_url = 'https://www.kcl.ac.uk';
		for ($i=1; $i < 18; $i++) 
		{ 
			$res = json_decode(file_get_contents(__DIR__.'/'.$web_name.'/'.$i.'.php'), true);
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
			    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
			    if($empty) Capsule::table('info')->insert($temp);
			}
		}
		echo 'kcl analyse completed!'.PHP_EOL;
	}


	// Manchester
	public static function Manchester()
	{
		// ajax 获取的xml
		$arr = ['courses'=>'http://www.manchester.ac.uk/study/masters/courses/list/xml/','programmes'=>'http://www.manchester.ac.uk/study/postgraduate-research/programmes/list/xml/'];
		$base_url = ['courses'=>'http://www.manchester.ac.uk/study/masters/courses/list/','programmes'=>'http://www.manchester.ac.uk/study/postgraduate-research/programmes/list/'];
		$web_name = 'Manchester';
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
				$response = $client->get($value);
				@mkdir(PROJECT_APP_DOWN, 0777, true);
				echo 'download Manchester completed!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象{
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				// $base_url='www.ucl.ac.uk';
				foreach($dom->find('li') as $tr)
				{
					$url = $base_url[$key].$tr->find('a',0)->href;
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
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
				}
				// 清理内存防止内存泄漏
				$dom->clear(); 
			}
		}
		echo 'Manchester analyse completed!'.PHP_EOL;
	}
	
	// Bristol
	public static function Bristol()
	{
		$web_name = 'Bristol';
		if(!is_file(PROJECT_APP_DOWN.$web_name.'.html'))
		{
			$web = 'http://www.bris.ac.uk/study/postgraduate/search/';
			// 解析页面
			$client = new Client();
			$response = $client->get($web);
			@mkdir(PROJECT_APP_DOWN, 0777, true);
			echo 'download Bristol completed!'.PHP_EOL;
			// 保存首页
			file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		}
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		{
			$base_url='http://www.bris.ac.uk/study/postgraduate/search/';
			foreach($dom->find('.prog-az-listings .prog-results-list li') as $li)
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
			    	'model'=>$li->find('.prog-type',0)?$li->find('.prog-type',0)->plaintext:'',
			    	'web_name'=>$web_name
			    ];
			    // 入库
			    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
			    if($empty) Capsule::table('info')->insert($temp);
			}
			echo 'Bristol analyse completed!'.PHP_EOL;
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
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
				$response = $client->get($value,['verify'=>false]);
				@mkdir(PROJECT_APP_DOWN, 0777, true);
				echo 'download Glasgow successful!';
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				// $base_url='www.ucl.ac.uk';
				foreach($dom->find('#jquerylist li') as $tr)
				{
					$url = $base_url.$tr->find('a',0)->href;
					$que = $tr->find('a',0)->find('span',0)?$tr->find('a',0)->find('span',0)->plaintext:'';
					$title = str_replace([$que,'</span>'], '',$tr->find('a',0)->plaintext);
				    $temp = [
				    	'url' => $url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'des' => $key,
				    	'qualification'=>'',
				    	'model'=>str_replace(['[',']'], '',$que),
				    	'web_name'=>$web_name
				    ];
				    // 入库
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
				}
				echo 'ucl analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
	}

	// Warwick - 注意：这里教研ssl证书，请在phpini指定证书路径以及下载最新的证书
	public static function Warwick()
	{
		$web_name = 'Warwick';
		$web = 'https://warwick.ac.uk/study/postgraduate/courses-2018/';
		if(!file_exists(PROJECT_APP_DOWN.$web_name.'.html'))
		{
			// 解析页面
			$client = new Client();
			$response = $client->get($web,['verify'=>false]);
			@mkdir(PROJECT_APP_DOWN, 0777, true);
			echo 'download Warwick completed!'.PHP_EOL;
			// 保存首页
			file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		}
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.'Warwick.html')))
		{
			// var_dump($dom);
			$base_url='http://www.bris.ac.uk/study/postgraduate/search/';
			foreach($dom->find('.sb-glossary-terms a') as $li)
			{
				if($li->href)
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
				    // 入库
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
					
				}
			}
			//右侧
			$i=1;
			foreach($dom->find('.box5 h5') as $key=>$li)
			{
				if(isset($li->find('a',0)->href))
				{
					$url = $li->find('a',0)->href;
					if(!preg_match('/(http:\/\/)|(https:\/\/)/i', $url))
					{
						$url = $web.$url;
					}
					$title = $li->find('a',0)->plaintext;
					if(!is_file(PROJECT_APP_DOWN.'/'.$web_name.'/'.$i.'.html'))
					{	
						echo $url.PHP_EOL;
						// 解析页面
						$client = new Client();
						$response = $client->get($url);
						@mkdir(PROJECT_APP_DOWN.'/'.$web_name, 0777, true);
						echo 'download Warwick-1 successful!'.PHP_EOL;
						// 保存首页
						file_put_contents(PROJECT_APP_DOWN.'/'.$web_name.'/'.$i.'.html', $response->getBody());
					}
					if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.'/'.$web_name.'/'.$i.'.html')))
					{
						foreach($dom->find('.column-1 table') as $li)
						{
							if(@$li->find('tr',0)->find('h3',0)->plaintext)
							{
								$title = $li->find('tr',0)->find('h3',0)->plaintext;
							    $temp = [
							    	'url' => $url,
							    	'status' => 'wait',
							    	'md5_url' => md5($url),
							    	'title' => $title,
							    	'des' => $li->find('tr',1)->find('td',1)->find('p',1)->plaintext,
							    	'qualification'=>$li->find('tr',1)->find('td',2)->find('p',1)->plaintext,
							    	'model'=>$li->find('tr',2)->find('td',0)->find('p',1)->plaintext,
							    	'web_name'=>$web_name
							    ];
							    // 入库
							    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
							    if($empty) Capsule::table('info')->insert($temp);
							}
						}
						// 清理内存防止内存泄漏
						$dom-> clear();
						
					}
					$i++;
				}
			}
		}
	}

	// Sheffield
	public static function Durham()
	{
		echo 'downloading Durham!'.PHP_EOL;
		$web_name = 'Durham';
		$arr=['all'=>'https://www.dur.ac.uk/courses/all/#indexA','research'=>'https://www.dur.ac.uk/study/pg/studyoptions/research/'];
		foreach ($arr as $key => $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				$web = 'https://www.dur.ac.uk/courses/all/#indexA';
				// 解析页面
				$client = new Client();
				$response = $client->get($web,['verify'=>false]);
				@mkdir(PROJECT_APP_DOWN, 0777, true);
				echo 'download Durham successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				$base_url='https://www.dur.ac.uk';
				if($key == 'all')
				{
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
							    // 入库
							    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
							    if($empty) Capsule::table('info')->insert($temp);
							}
							
						}
					}
				}
				if($key=='research')
				{
					foreach($dom->find('#content tr') as $li)
					{
						// 第二层
						
						$url = $li->find('a',0)->href;
						$title = $li->find('a',0)->plaintext;
					    $temp = [
					    	'url' => $url,
					    	'status' => 'wait',
					    	'md5_url' => md5($url),
					    	'title' => $title,
					    	'des' =>$li->find('td',1)?$li->find('td',1)->plaintext:'',
					    	'qualification'=>$li->find('td',2)?$li->find('td',2)->plaintext:'',
					    	'model'=>$li->find('td',3)?$li->find('td',3)->plaintext:'',
					    	'home'=>'research',
					    	'web_name'=>$web_name
					    ];
					    // 入库
					    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
					    Capsule::table('info')->insert($temp);
					}
					
				}
				echo 'Durham analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
	}

	// Sheffield
	public static function Sheffield()
	{
		$web_name = 'Sheffield';
		$arr=['courses'=>'https://www.sheffield.ac.uk/postgraduate/taught/courses/all','research'=>'https://www.sheffield.ac.uk/postgraduate/research/areas/index'];
		foreach ($arr as $key => $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
				$response = $client->get($value,['verify'=>false]);
				@mkdir(PROJECT_APP_DOWN, 0777, true);
				echo 'download Sheffield successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				$base_url='https://www.sheffield.ac.uk';
				if($key=='courses')
				{
					foreach($dom->find('.col-md-19 p') as $li)
					{
						if(isset($li->find('a',0)->href))
						{
							$url = $base_url.$li->find('a',0)->href;
							$title = $li->find('a',0)->plaintext;
						    $temp = [
						    	'url' => $url,
						    	'status' => 'wait',
						    	'md5_url' => md5($url),
						    	'title' => $title,
						    	'des' =>$key,
						    	'web_name'=>$web_name
						    ];
						    // 入库
						    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
						    if($empty) Capsule::table('info')->insert($temp);
						}
					}
				}
				if($key == 'research')
				{
					foreach($dom->find('.feature p') as $li)
					{
						// 第二层
						if(isset($li->find('a',0)->href))
						{
							$url = $li->find('a',0)->href;
							$title = $li->find('a',0)->plaintext;
						    $temp = [
						    	'url' => $url,
						    	'status' => 'wait',
						    	'md5_url' => md5($url),
						    	'title' => $title,
						    	'des'=>'research',
						    	'web_name'=>$web_name
						    ];
						    // 入库
						    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
						    if($empty) Capsule::table('info')->insert($temp);
					    }		
					}
					
				}
				echo 'Sheffield analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
	}

	// Queenmary
	public static function Queenmary()
	{
		echo 'Queenmary downloading!'.PHP_EOL;
		$web_name = 'Queenmary';
		for ($i=0; $i < 27; $i++) 
		{ 
			if(!is_file(PROJECT_APP_DOWN.'/'.$web_name.'one/'.$i.'.html'))
			{
				$web = 'http://search.qmul.ac.uk/s/search.html?collection=queenmary-coursefinder-pg&query=&f.Mode%7CM=full+time&sort=title&start_rank='.$i.'1';
				// 解析页面
				$client = new Client();
				$response = $client->get($web);
				@mkdir(PROJECT_APP_DOWN.'/'.$web_name.'one', 0777, true);
				echo 'download Queenmary completed!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.'/'.$web_name.'one/'.$i.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.'/'.$web_name.'one/'.$i.'.html')))
			{
				$base_url='http://search.qmul.ac.uk';
				foreach($dom->find('#search-results li') as $li)
				{
					$url = $base_url.$li->find('.result-title',0)->find('a',0)->href;
					$title = $li->find('.result-title',0)->find('a',0)->plaintext;
				    $temp = [
				    	'url' => $url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'des' =>$li->find('.result-subject',0)->plaintext ,
				    	'web_name'=>$web_name
				    ];
				    // 入库
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
				}
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
		echo 'Queenmaryone analyse completed!'.PHP_EOL;
		for ($i=1; $i < 7; $i++) 
		{ 
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(__DIR__.'/'.$web_name.'/'.$i.'.html')))
			{
				$base_url='http://search.qmul.ac.uk';
				foreach($dom->find('.API_resultItem') as $li)
				{
					$url = 'https://www.qmul.ac.uk/postgraduate/research/';
					$title = $li->find('.API_phdTitle',0)->find('a',0)->plaintext;
				    $temp = [
				    	'url' => $url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'des' =>$li->find('.API_categoryDiv',0)->find('span',0)->plaintext,
				    	'qualification'=>$li->find('.API_categoryDiv',0)->find('span',1)->plaintext,
				    	'model'=>$li->find('.API_categoryDiv',0)->find('span',2)->plaintext,
				    	'home'=>'research',
				    	'web_name'=>$web_name
				    ];
				    // 入库
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
				}
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
		echo 'Queenmarysearch analyse completed!'.PHP_EOL;
	}

	// Exeter
	public static function Exeter()
	{
		$arr = ['courses'=>'https://www.exeter.ac.uk/postgraduate/all-courses/','degrees'=>'https://www.exeter.ac.uk/pg-research/degrees/'];
		$web_name = 'Exeter';
		$base_url = 'https://www.exeter.ac.uk';
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
					$response = $client->get($value,['verify'=>false]);
					@mkdir(PROJECT_APP_DOWN, 0777, true);
					echo 'download Exeter completed!'.PHP_EOL;
					// 保存首页
					file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				if($key=='courses')
				{
					foreach($dom->find('#all-courses-A-Z li') as $tr)
					{
						$url = $base_url.$tr->find('a',0)->href;
						$title = $tr->find('a',0)->plaintext;
					    $temp = [
					    	'url' => $url,
					    	'status' => 'wait',
					    	'md5_url' => md5($url),
					    	'title' => $title,
					    	'des' => $key,
					    	'web_name'=>$web_name
					    ];
					    // 入库
					    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
					    if($empty) Capsule::table('info')->insert($temp);
					}
				}
				if($key=='degrees')
				{
					foreach($dom->find('#main-content li') as $tr)
					{
						if($tr->parent() && $tr->parent()->class !='menu')
						{
							$url = $base_url.$tr->find('a',0)->href;
							$title = $tr->find('a',0)->plaintext;
						    $temp = [
						    	'url' => $url,
						    	'status' => 'wait',
						    	'md5_url' => md5($url),
						    	'title' => $title,
						    	'des' => $key,
						    	'web_name'=>$web_name
						    ];
						    // 入库
						    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
						    if($empty) Capsule::table('info')->insert($temp);
						}
					}
				}
				echo 'ucl analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
	}

	// Southampton
	public static function Southampton()
	{
		$arr = ['taught'=>'https://www.southampton.ac.uk/courses/taught-postgraduate.page','research'=>'https://www.southampton.ac.uk/courses/research-postgraduate.page'];
		$web_name = 'Southampton';
		$base_url = 'https://www.southampton.ac.uk';
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
					$response = $client->get($value,['verify'=>false]);
					@mkdir(PROJECT_APP_DOWN, 0777, true);
					echo 'download Southampton successful!'.PHP_EOL;
					// 保存首页
					file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				if($key == 'taught')
				{
					// $base_url='www.ucl.ac.uk';
					foreach($dom->find('.uos-course-group') as $tr)
					{
						foreach($tr->find('a') as $a)
						{
							$url = $base_url.$a->href;
							$title = $a->plaintext;
						    $temp = [
						    	'url' => $url,
						    	'status' => 'wait',
						    	'md5_url' => md5($url),
						    	'title' => $title,
						    	'des' => $key,
						    	'model'=>$tr->find('dt',0)->plaintext,
						    	'web_name'=>$web_name
						    ];
						    // 入库
						    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
						    if($empty) Capsule::table('info')->insert($temp);
						}
					}
					echo 'Southamptontaught analyse completed!'.PHP_EOL;
					// 清理内存防止内存泄漏
				}
				if($key == 'research')
				{
					// $base_url='www.ucl.ac.uk';
					foreach($dom->find('.uos-tier-secondary li') as $tr)
					{
						$url = $base_url.$tr->find('a',0)->href;
						$title = $tr->find('a',0)->plaintext;
					    $temp = [
					    	'url' => $url,
					    	'status' => 'wait',
					    	'md5_url' => md5($url),
					    	'title' => $title,
					    	'des' => $key,
					    	'web_name'=>$web_name
					    ];
					    // 入库
					    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
					    if($empty) Capsule::table('info')->insert($temp);
					}
					echo 'Southamptonresearch analyse completed!'.PHP_EOL;
					// 清理内存防止内存泄漏
				}
				$dom-> clear(); 

			}
		}
	}

	// York
	public static function York()
	{
		$arr = ['taught'=>'https://www.york.ac.uk/study/postgraduate/courses/all?mode=taught','research'=>'https://www.york.ac.uk/study/postgraduate/courses/all?mode=research'];
		$web_name = 'York';
		$base_url = 'https://www.york.ac.uk';
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
					$response = $client->get($value,['verify'=>false]);
					@mkdir(PROJECT_APP_DOWN, 0777, true);
					echo 'download York successful!'.PHP_EOL;
					// 保存首页
					file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				// $base_url='www.ucl.ac.uk';
				foreach($dom->find('table tbody tr') as $tr)
				{
					if($tr->find('td',0) && !empty($tr->find('td',0)->find('a',0)->href))
					{
						$url = $tr->find('td',0)->find('a',0)->href;
						$title = $tr->find('td',0)->find('a',0)->plaintext;
					    $temp = [
					    	'url' => $url,
					    	'status' => 'wait',
					    	'md5_url' => md5($url),
					    	'title' => $title,
					    	'des' => $key,
					    	'qualification'=>$tr->find('.detail li',0)?$tr->find('.detail li',0)->plaintext:'',
					    	'model'=>$tr->find('.detail li',1)?$tr->find('.detail li',1)->plaintext:'',
					    	'home'=>$tr->find('.detail li',2)?$tr->find('.detail li',2)->plaintext:'',
					    	'overseas'=>$tr->find('.code a',0)?$tr->find('.code a',0)->plaintext:'',
					    	'code_url'=>$tr->find('.code a',0)?$base_url.$tr->find('.code a',0)->href:'',
					    	'web_name'=>$web_name
					    ];
					    // 入库
					    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
					    if($empty) Capsule::table('info')->insert($temp);
					}
				}
				echo 'York analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 

			}
		}
	}

	// Leeds
	public static function Leeds()
	{
		$arr = ['PGT'=>'https://courses.leeds.ac.uk/course-search?query=&type=PGT','PGR'=>'https://courses.leeds.ac.uk/course-search?query=&type=PGR&term=201819'];
		$web_name = 'Leeds';
		$base_url = 'https://courses.leeds.ac.uk';
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
					$response = $client->get($value,['verify'=>false]);
					@mkdir(PROJECT_APP_DOWN, 0777, true);
					echo 'download Leeds successful!'.PHP_EOL;
					// 保存首页
					file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				// $base_url='www.ucl.ac.uk';
				foreach($dom->find('tbody tr') as $tr)
				{

					if($tr->class != 'hidden' && !empty($tr->find('.title a',0)->href))
					{
						$url = $tr->find('.title a',0)->href;
						$title = $tr->find('.title a',0)->plaintext;
						if($key=='PGT')
						{
						    $temp = [
						    	'url' => $url,
						    	'status' => 'wait',
						    	'md5_url' => md5($url),
						    	'title' => $title,
						    	'des' => $key,
						    	'qualification'=>$tr->find('td',1)?$tr->find('td',1)->plaintext:'',
						    	'model'=>$tr->find('td',2)?$tr->find('td',2)->plaintext:'',
						    	'web_name'=>$web_name
						    ];
						}
						if($key=='PGR')
						{
						    $temp = [
						    	'url' => $url,
						    	'status' => 'wait',
						    	'md5_url' => md5($url),
						    	'title' => $title,
						    	'des' => $key,
						    	'web_name'=>$web_name
						    ];
						}
					    // 入库
					    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
					    if($empty) Capsule::table('info')->insert($temp);
					}
				}
				echo 'Leeds analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 

			}
		}
	}

	// Birmingham
	public static function Birmingham()
	{
		$zimu  = ['A','B','C','D','E','F','G','H','I','L','M','N','O','P','R','S','T','U','V','W'];
		$arr = ['courses'=>'https://www.birmingham.ac.uk/postgraduate/courses/search.aspx?CurrentTab=AtoZ&CourseComplete_AtoZ_AtoZLetter=','research'=>'https://www.birmingham.ac.uk/postgraduate/courses/research/search.aspx?CurrentTab=AtoZ&CourseComplete_AtoZ_AtoZLetter='];
		$web_name = 'Birmingham';
		$base_url = 'https://www.birmingham.ac.uk';
		foreach ($arr as $key=> $value) 
		{
			if($key == 'courses')
			{
				$zimu  = ['A','B','C','D','E','F','G','H','I','L','M','N','O','P','R','S','T','U','V','W'];
				foreach ($zimu as $k => $v) 
				{
					if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'/'.$v.'.html'))
					{
						$get_url = $value.$v;
						// 解析页面
						$client = new Client();
						$response = $client->get($get_url,['verify' => false]);
						@mkdir(PROJECT_APP_DOWN.$web_name.'_'.$key, 0777, true);
						echo 'download Birmingham successful!'.PHP_EOL;
						// 保存首页
						file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'/'.$v.'.html', $response->getBody());
					}
					if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'/'.$v.'.html')))
					{
						foreach($dom->find('.tablesaw tbody tr') as $tr)
						{

							if(!empty($tr->find('a',0)->href))
							{
								$url = $base_url.$tr->find('a',0)->href;
								$title = $tr->find('a',0)->plaintext;
							    $temp = [
							    	'url' => $url,
							    	'status' => 'wait',
							    	'md5_url' => md5($url),
							    	'title' => $title,
							    	'des' => $tr->find('td',2)->plaintext,
							    	'qualification'=>$tr->find('td',1)->plaintext,
							    	'model'=>$tr->find('td',3)->plaintext,
							    	'home'=>$key,
							    	'web_name'=>$web_name
							    ];
							    // 入库
							    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
							    if($empty) Capsule::table('info')->insert($temp);
							}
						}
						// 清理内存防止内存泄漏
						$dom-> clear(); 

					}
				}
				
			}
			if($key == 'research')
			{
				$zimu  = ['A','B','C','D','E','F','G','H','I','L','M','N','O','P','R','S','T'];
				foreach ($zimu as $k => $v) 
				{
					if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'/'.$v.'.html'))
					{
						$get_url = $value.$v;
						// 解析页面
						$client = new Client();
						$response = $client->get($get_url,['verify' => false]);
						@mkdir(PROJECT_APP_DOWN.$web_name.'_'.$key, 0777, true);
						echo 'download Birmingham successful!'.PHP_EOL;
						// 保存首页
						file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'/'.$v.'.html', $response->getBody());
					}
					if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'/'.$v.'.html')))
					{
						foreach($dom->find('.tablesaw tbody tr') as $tr)
						{

							if(!empty($tr->find('a',0)->href))
							{
								$url = $base_url.$tr->find('a',0)->href;
								$title = $tr->find('a',0)->plaintext;
							    $temp = [
							    	'url' => $url,
							    	'status' => 'wait',
							    	'md5_url' => md5($url),
							    	'title' => $title,
							    	'des' => $tr->find('td',2)->plaintext,
							    	'qualification'=>$tr->find('td',1)->plaintext,
							    	'model'=>$tr->find('td',3)->plaintext,
							    	'home'=>$key,
							    	'web_name'=>$web_name
							    ];
							    // 入库
							    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
							    if($empty) Capsule::table('info')->insert($temp);
							}
						}
						// 清理内存防止内存泄漏
						$dom-> clear(); 

					}
				}
				
			}
		}
		echo 'Birmingham analyse completed!'.PHP_EOL;
	}

	// St Andrews
	public static function St_Andrews()
	{
		$web_name = 'St_Andrews';
		$base_url = 'https://www.st-andrews.ac.uk';
		if(!is_file(PROJECT_APP_DOWN.$web_name.'.html'))
		{
			$get_url = 'https://www.st-andrews.ac.uk/subjects/';
			// 解析页面
			$client = new Client();
			$response = $client->get($get_url,['verify' => false]);
			@mkdir(PROJECT_APP_DOWN, 0777, true);
			echo 'download St_Andrews successful!'.PHP_EOL;
			// 保存首页
			file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		}
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		{
			foreach($dom->find('.col-sm-3 h4') as $li)
			{

				$title = $li->find('a',0)->plaintext;
				$url = $li->find('a',0)->href;
				if(!preg_match('/(http:\/\/)|(https:\/\/)/i', $url))
				{
					$url = $base_url.$url;
				}
				if(!is_file(PROJECT_APP_DOWN.$web_name.'/'.$title.'.html'))
				{
					// 解析页面
					$client = new Client();
					$response = $client->get($url,['verify' => false]);
					@mkdir(PROJECT_APP_DOWN.$web_name, 0777, true);
					echo 'download St_Andrews successful!'.PHP_EOL;
					// 保存首页
					file_put_contents(PROJECT_APP_DOWN.$web_name.'/'.$title.'.html', $response->getBody());
				}
				// 创建dom对象
				if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'/'.$title.'.html')))
				{

					if( $dom->find('.sta-grey-light',0) && $son = $dom->find('.sta-grey-light',0)->find('.container .row',1)->find('.col-sm-6',1))
					{
						foreach($son->find('tr') as $li)
						{
								$sonurl = $li->find('a',0)->href;
								$sontitle = $li->find('a',0)->plaintext;
							    $temp = [
							    	'url' =>$sonurl,
							    	'status' => 'wait',
							    	'md5_url' => md5($sonurl),
							    	'title' => $sontitle,
							    	'des' => $son->find('p',0)?$son->find('p',0)->plaintext:'',
							    	'qualification'=>$li->find('td',1)?$li->find('td',1)->plaintext:'',
							    	'model'=>$title,
							    	'code_url'=>$url,
							    	'web_name'=>$web_name
							    ];
							    // // 入库
							    $empty = Capsule::table('info')->where('md5_url',md5($sonurl))->get()->isEmpty();
							    if($empty) Capsule::table('info')->insert($temp);
						}
						
					}
					// 清理内存防止内存泄漏
					$dom-> clear(); 
				}
			}
			echo 'St_Andrews Andrews analyse completed!'.PHP_EOL;
		}
	}

	// Nottingham
	public static function Nottingham()
	{
		$web_name = 'Nottingham';
		$zimu  = ['A','B','C','D','E','F','G','H','I','L','M','N','O','P','Q','R','S','T','U','V','W'];
		foreach ($zimu as $key => $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'/'.$value.'.html'))
			{
				$web = 'https://www.nottingham.ac.uk/pgstudy/courses/courses.aspx?AZListing_AtoZLetter='.$value;
				// 解析页面
				$client = new Client();
				$response = $client->get($web,['verify'=>false]);
				@mkdir(PROJECT_APP_DOWN.$web_name, 0777, true);
				echo 'download Nottingham successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'/'.$value.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'/'.$value.'.html')))
			{
				$base_url='http://www.bris.ac.uk/study/postgraduate/search/';
				foreach($dom->find('.sys_itemslist .sys_subitem') as $li)
				{
					$url = $li->find('a',0)->href;
					$title = $li->find('a',0)->plaintext;
				    $temp = [
				    	'url' => $base_url.$url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'model'=>$value,
				    	'web_name'=>$web_name
				    ];
				    // 入库
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
				}
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
		echo 'Nottingham analyse completed!'.PHP_EOL;
	}

	// Sussex
	public static function Sussex()
	{
		$arr = ['masters'=>'http://www.sussex.ac.uk/study/masters/','phd'=>'http://www.sussex.ac.uk/study/phd'];
		$web_name = 'Sussex';
		$base_url = 'http://www.sussex.ac.uk';
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
				$response = $client->get($value);
				@mkdir(PROJECT_APP_DOWN, 0777, true);
				echo 'download Sussex successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				// $base_url='www.ucl.ac.uk';
				foreach($dom->find('.gutter-medium li') as $tr)
				{
					if($tr->find('a') && $url = $tr->find('a',0)->href)
					{
						
						$title = $tr->find('a',0)->plaintext;
					    $temp = [
					    	'url' => $url,
					    	'status' => 'wait',
					    	'md5_url' => md5($url),
					    	'title' => $title,
					    	'des' => $key,
					    	'web_name'=>$web_name
					    ];
					    // 入库
					    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
					    if($empty) Capsule::table('info')->insert($temp);
					}
				}
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
		echo 'Sussex analyse completed!'.PHP_EOL;
	}

	// Lancaster
	public static function Lancaster()
	{
		$web_name = 'Lancaster';
		$base_url='http://www.lancaster.ac.uk/study/postgraduate/postgraduate-courses/';
		if(!is_file(PROJECT_APP_DOWN.$web_name.'.html'))
		{
			// 解析页面
			$client = new Client();
			$response = $client->get($base_url);
			@mkdir(PROJECT_APP_DOWN, 0777, true);
			echo 'download Lancaster successful!'.PHP_EOL;
			// 保存首页
			file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		}
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		{
			foreach($dom->find('.a-z li') as $li)
			{
				if(empty($li->parent()->class))
				{
					$url = $li->find('a',0)?$li->find('a',0)->href:'';
					$title = $li->find('a',0)?$li->find('a',0)->plaintext:'';
				    $temp = [
				    	'url' => $base_url.$url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'web_name'=>$web_name
				    ];
				    // 入库
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
				}
			}
			echo 'Lancaster analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}

	// Leicester
	public static function Leicester()
	{
		$web_name = 'Leicester';
		for ($i=1; $i < 15; $i++) 
		{ 
			if(!is_file(PROJECT_APP_DOWN.$web_name.'/'.$i.'.html'))
			{
				$web = 'https://le.ac.uk/courses?q=&level=Postgraduate&Page='.$i;
				// 解析页面
				$client = new Client();
				$response = $client->get($web,['verify'=>false]);
				@mkdir(PROJECT_APP_DOWN.$web_name, 0777, true);
				echo 'download Leicester successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'/'.$i.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'/'.$i.'.html')))
			{
				foreach($dom->find('.search-result-list li') as $li)
				{
					$url = $li->find('a',0)->href;
					$title = $li->find('a',0)->plaintext;
				    $temp = [
				    	'url' => $url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'des' => $li->find('p',1)->plaintext,
				    	'web_name'=>$web_name
				    ];
				    // 入库
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
				}
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
		echo 'Leicester analyse completed!'.PHP_EOL;
	}

	// Cardiff
	public static function Cardiff()
	{
		$arr = ['taught'=>'https://www.cardiff.ac.uk/study/postgraduate/taught/courses/a-to-z','research'=>'https://www.cardiff.ac.uk/study/postgraduate/research/programmes/a-to-z'];
		$web_name = 'Cardiff';
		$base_url = 'https://www.cardiff.ac.uk';
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
				$response = $client->get($value,['verify'=>false]);
				@mkdir(PROJECT_APP_DOWN, 0777, true);
				echo 'download Cardiff successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				foreach($dom->find('.dictionary tr') as $tr)
				{
					if($tr->find('a') && $url = $tr->find('a',0)->href)
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
					    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
					    if($empty) Capsule::table('info')->insert($temp);
					}
				}
				echo 'masters analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 

			}
		}
	}

	// Newcastle
	public static function Newcastle()
	{
		$web_name = 'Newcastle';
		$base_url = 'http://www.ncl.ac.uk';
		$web = 'http://www.ncl.ac.uk/postgraduate/courses/#a-z';
		if(!is_file(PROJECT_APP_DOWN.$web_name.'.html'))
		{
			// 解析页面
			$client = new Client();
			$response = $client->get($base_url);
			@mkdir(PROJECT_APP_DOWN, 0777, true);
			echo 'download Newcastle successful!'.PHP_EOL;
			// 保存首页
			file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		}
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		{
			foreach($dom->find('#azlink li') as $tr)
			{
				if($tr->find('a') && $url = $tr->find('a',0)->href)
				{
					$title = $tr->find('a',0)->plaintext;
				    $temp = [
				    	'url' => $base_url.$url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'web_name'=>$web_name
				    ];
				    // 入库
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
				}
			}
			echo 'masters analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}

	// Liverpool
	public static function Liverpool()
	{
		$arr = ['taught'=>'https://www.liverpool.ac.uk/study/postgraduate-taught/courses/','research'=>'https://www.liverpool.ac.uk/study/postgraduate-research/degrees/'];
		$web_name = 'Liverpool';
		$base_url = 'https://www.liverpool.ac.uk';
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
				$response = $client->get($value,['verify'=>false]);
				@mkdir(PROJECT_APP_DOWN, 0777, true);
				echo 'download Liverpool successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				if($key=='taught')
				{
					foreach($dom->find('#departments tbody tr') as $tr)
					{
						if($tr->find('a') && $url = $tr->find('a',0)->href)
						{
							
							$title = $tr->find('a',0)->plaintext;
						    $temp = [
						    	'url' => $value.$url,
						    	'status' => 'wait',
						    	'md5_url' => md5($url),
						    	'title' => $title,
						    	'des' => $key,
						    	'code'=>$tr->find('td',1)->find('a',0)->plaintext,
						    	'code_url'=>$tr->find('td',1)->find('a',0)->href,
						    	'web_name'=>$web_name
						    ];
						    // 入库
						    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
						    if($empty) Capsule::table('info')->insert($temp);
						}
					}
					
				}
				if($key=='research')
				{
					foreach($dom->find('.courselist .course') as $tr)
					{
						if($tr->find('a') && $url = $tr->find('a',0)->href)
						{
							
							$title = $tr->find('a',0)->plaintext;
						    $temp = [
						    	'url' => $base_url.$url,
						    	'status' => 'wait',
						    	'md5_url' => md5($url),
						    	'title' => $title,
						    	'des' => $key,
						    	'qualification'=>$tr->find('li',0)->find('span',0)->plaintext,
						    	'model'=>$tr->find('li',1)->find('span',0)->plaintext,
						    	'overseas'=>$tr->find('li',2)?$tr->find('li',2)->plaintext:'',
						    	'web_name'=>$web_name
						    ];
						    // 入库
						    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
						    if($empty) Capsule::table('info')->insert($temp);
						}
					}
				}
				echo 'Liverpool analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 
		}
		}
	}

	// Aberdeen
	public static function Aberdeen()
	{
		$arr = ['taught'=>'https://www.abdn.ac.uk/study/postgraduate-taught/degree-programmes/?limit=All','research'=>'https://www.abdn.ac.uk/study/postgraduate-research/research-areas/?page=2&limit=All'];
		$web_name = 'Aberdeen';
		$base_url = 'https://www.abdn.ac.uk';
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
				$response = $client->get($value,['verify'=>false]);
				@mkdir(PROJECT_APP_DOWN, 0777, true);
				echo 'download Aberdeen successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				foreach($dom->find('tbody tr') as $tr)
				{
					if($tr->find('a') && $url = $tr->find('a',0)->href)
					{
						$title = $tr->find('a',0)->plaintext;
					    $temp = [
					    	'url' => $url,
					    	'status' => 'wait',
					    	'md5_url' => md5($url),
					    	'title' => $title,
					    	'des' => $key,
					    	'qualification'=>$tr->find('td',1)?$tr->find('td',1)->plaintext:'',
					    	'model'=>$tr->find('td',2)?$tr->find('td',2)->plaintext:'',
					    	'web_name'=>$web_name
					    ];
					    // 入库
					    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
					    if($empty) Capsule::table('info')->insert($temp);
					}
				}
				echo 'Aberdeen analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
	}

	// Dundee
	public static function Dundee()
	{
		$web_name = 'Dundee';
		$base_url = 'https://search.dundee.ac.uk';
		for ($i=0; $i < 19; $i++) 
		{ 
			if(!is_file(PROJECT_APP_DOWN.$web_name.'/'.$i.'.html'))
			{
				$web = 'https://search.dundee.ac.uk/s/search.html?collection=courses&query=&sort=title&f.Level|L=pgt&f.Mode|M=ft&start_rank='.$i.'1';
				// 解析页面
				$client = new Client();
				$response = $client->get($web,['verify'=>false]);
				@mkdir(PROJECT_APP_DOWN.$web_name, 0777, true);
				echo 'download Dundee successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'/'.$i.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'/'.$i.'.html')))
			{
				foreach($dom->find('#search-results li') as $li)
				{
					if($li->parent()->class=='courses_results')
					{
						$url = $li->find('a',0)->href;
						$title = $li->find('a',0)->plaintext;
					    $temp = [
					    	'url' => $base_url.$url,
					    	'status' => 'wait',
					    	'md5_url' => md5($url),
					    	'title' => $title,
					    	'des' => $li->find('p',0)->plaintext,
					    	'qualification'=>$li->find('li',0)?$li->find('li',0)->plaintext:'',
					    	'model'=>$li->find('li',1)?$li->find('li',1)->plaintext:'',
					    	'code'=>$li->find('li',2)?$li->find('li',2)->plaintext:'',
					    	'home'=>$li->find('li',3)?$li->find('li',3)->plaintext:'',
					    	'web_name'=>$web_name
					    ];
				    	// 入库
				    	$empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
					    if($empty) Capsule::table('info')->insert($temp);
						
					}
				}
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
		echo 'Dundee analyse completed!'.PHP_EOL;
	}

	// RHUL
	public static function RHUL()
	{
		$web_name = 'RHUL';
		$zimu  = ['A','B','C','D','E','F','G','H','I','L','M','P','Q','R','S','T','V'];
		$base_url='https://www.royalholloway.ac.uk';
		foreach ($zimu as $key => $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'/'.$value.'.html'))
			{
				$web = 'https://www.royalholloway.ac.uk/courses/postgraduate/home.aspx?GenericList_AtoZLetter='.$value;
				// 解析页面
				$client = new Client();
				$response = $client->get($web,['verify'=>false]);
				@mkdir(PROJECT_APP_DOWN.$web_name, 0777, true);
				echo 'download RHUL successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'/'.$value.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'/'.$value.'.html')))
			{
				foreach($dom->find('#GenericList_List .sys_subitem') as $li)
				{
					$url = $li->find('a',0)->href;
					$title = $li->find('a',0)->plaintext;
				    $temp = [
				    	'url' => $base_url.$url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'model'=>$value,
				    	'web_name'=>$web_name
				    ];
				    // 入库
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
				}
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
		echo 'RHUL analyse completed!'.PHP_EOL;
	}

	// Queen_belfast
	public static function Queen_belfast()
	{
		$web_name = 'Queen_belfast';
		if(!is_file(PROJECT_APP_DOWN.$web_name.'.html'))
		{
			$web = 'http://www.qub.ac.uk/courses/postgraduate-taught/?keyword=';
			// 解析页面
			$client = new Client();
			$response = $client->get($web);
			@mkdir(PROJECT_APP_DOWN, 0777, true);
			echo 'download Queen_belfast successful!'.PHP_EOL;
			// 保存首页
			file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		}
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		{
			$base_url='http://www.qub.ac.uk';
			foreach($dom->find('tbody tr') as $li)
			{
				// 第二层
				if($li->find('a'))
				{
					$url = $li->find('a',0)->href;
					$title = $li->find('a',0)->plaintext;
				    $temp = [
				    	'url' => $base_url.$url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'qualification'=>$li->find('td',0)->plaintext,
				    	'model'=>$li->find('td',1)->plaintext,
				    	'web_name'=>$web_name
				    ];
				    // 入库
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
				}
			}
			echo 'diguo analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}

	// Reading
	public static function Reading()
	{
		$web_name = 'Reading';
		if(!is_file(PROJECT_APP_DOWN.$web_name.'.html'))
		{
			$web = 'https://www.reading.ac.uk/Ready-to-Study.aspx';
			// 解析页面
			$client = new Client();
			$response = $client->get($web,['verify'=>false]);
			@mkdir(PROJECT_APP_DOWN, 0777, true);
			echo 'download Reading successful!'.PHP_EOL;
			// 保存首页
			file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		}
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		{
			$base_url='https://www.reading.ac.uk';
			foreach($dom->find('#TabPanel2 .no-indent li') as $li)
			{
				// 第二层
				if($li->find('a'))
				{
					$url = $li->find('a',0)->href;
					$title = $li->find('a',0)->plaintext;
				    $temp = [
				    	'url' => $base_url.$url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'web_name'=>$web_name
				    ];
				    // 入库
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
					
				}
			}
			echo 'diguo analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}

	// Bath
	public static function Bath()
	{
		$arr = ['taught'=>'http://www.bath.ac.uk/courses/postgraduate-2018/taught-postgraduate-courses/','research'=>'http://www.bath.ac.uk/guides/how-to-apply-for-doctoral-study/#select-a-research-programme'];
		$web_name = 'Bath';
		$base_url = 'http://www.bath.ac.uk';
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
				$response = $client->get($value,['verify'=>false]);
				@mkdir(PROJECT_APP_DOWN, 0777, true);
				echo 'download Bath successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				if($key=='taught')
				{
					foreach($dom->find('.action-list .single-item') as $tr)
					{
						foreach ($tr->find('li') as $son) 
						{
							if($son->find('a') && $url = $son->find('a',0)->href)
							{
								$title = $son->find('a',0)->plaintext;
							    $temp = [
							    	'url' => $base_url.$url,
							    	'status' => 'wait',
							    	'md5_url' => md5($url),
							    	'title' => $title,
							    	'des' => $tr->find('h1',0)->plaintext,
							    	'code'=>$key,
							    	'web_name'=>$web_name
							    ];
							    // 入库
							    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
							    if($empty) Capsule::table('info')->insert($temp);
							}
						}
					}
					echo 'Aberdeen analyse completed!'.PHP_EOL;
					// 清理内存防止内存泄漏
					$dom-> clear(); 
				}
				if($key=='research')
				{
					$i=1;
					foreach($dom->find('.medium-15 section',0)->find('li') as $tr)
					{
						if($tr->find('a') && $url = $tr->find('a',0)->href)
						{
							$parTitle = $tr->find('a',0)->plaintext;
							if(!is_file(PROJECT_APP_DOWN.$web_name.'/'.$i.'.html'))
							{
								// 解析页面
								$client = new Client();
								$response = $client->get($url,['verify'=>false]);
								@mkdir(PROJECT_APP_DOWN.$web_name, 0777, true);
								echo 'download file successful!'.PHP_EOL;
								// 保存首页
								file_put_contents(PROJECT_APP_DOWN.$web_name.'/'.$i.'.html', $response->getBody());
							}
							if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'/'.$i.'.html')))
							{
								foreach($dom->find('#content li') as $tr)
								{
									if($tr->find('a') && $sonurl=$tr->find('a',0)->href)
									{
										$title = $tr->find('a',0)->plaintext;
									    $temp = [
									    	'url' => $base_url.$sonurl,
									    	'status' => 'wait',
									    	'md5_url' => md5($sonurl),
									    	'title' => $title,
									    	'des' => $tr->find('font',-1)?trim($tr->find('font',-1)->plaintext,','):'',
									    	'model'=>$key,
									    	'code'=>$parTitle,
									    	'code_url'=>$url,
									    	'web_name'=>$web_name
									    ];
									    // 入库
									$empty = Capsule::table('info')->where('md5_url',md5($sonurl))->get()->isEmpty();
									if($empty) Capsule::table('info')->insert($temp);
									}
								}
							}
						}
						$i++;
					}
					echo 'Aberdeen analyse completed!'.PHP_EOL;
					// 清理内存防止内存泄漏
					$dom-> clear(); 
				}
			}
		}
	}

	// Essex
	public static function Essex()
	{
		$arr = ['courses'=>'https://www.essex.ac.uk/masters/courses','pgr'=>'https://www1.essex.ac.uk/pgr/'];
		$web_name = 'Essex';
		$base_url = ['courses'=>'https://www.essex.ac.uk','pgr'=>'https://www1.essex.ac.uk/pgr/'];
		$type = ['Undergraduate'=>'UG','Masters'=>'PGT'];
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
				$response = $client->get($value,['verify'=>false]);
				@mkdir(PROJECT_APP_DOWN, 0777, true);
				echo 'download Essex successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				if($key=='courses')
				{
					$i=1;
					foreach($dom->find('.padding-top-lg-desktop .grid__item') as $tr)
					{
						if($tr->find('a') && $url = $tr->find('a',0)->href)
						{
							//第一层
							$ptitle = $tr->find('.subject__title',0)->plaintext;
							if(!is_file(PROJECT_APP_DOWN.$web_name.'/'.$i.'.html'))
							{
								// 解析页面
								$client = new Client();
								$response = $client->get($base_url[$key].$url,['verify'=>false]);
								@mkdir(PROJECT_APP_DOWN.$web_name, 0777, true);
								echo 'download Essex successful!'.PHP_EOL;
								// 保存首页
								file_put_contents(PROJECT_APP_DOWN.$web_name.'/'.$i.'.html', $response->getBody());
							}
							if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'/'.$i.'.html')))
							{
								$j=0;
								foreach($dom->find('.container .text-category') as $son)
								{
									$num = $son->find('strong',0)?str_replace(['(',')'],'',$son->find('strong',0)->plaintext):0;
									if($num==0 && $son) $num=1;
									//有数据
									if($num>0)
									{
										$str = $dom->find('.tabs__nav a',$j)?$dom->find('.tabs__nav a',$j)->plaintext:'';
										//这里是要分页取数据的
										if($num>6)
										{
											if($dom->find('.load-more-container a',$j))
											{
												$getUrl = $base_url[$key].$dom->find('.load-more-container a',$j)->href;
												for ($p=1; $p<=(ceil($num/6)); $p++) 
												{ 
													$getUrl = preg_replace('/page=\d{1,2}/', 'page='.$p, $getUrl);
													//第一层
													if(!is_file(PROJECT_APP_DOWN.$web_name.'/'.$i.'/'.$type[$str].'_'.$p.'.html'))
													{
														// 解析页面
														$client = new Client();
														$response = $client->get($getUrl,['verify'=>false]);
														@mkdir(PROJECT_APP_DOWN.$web_name.'/'.$i, 0777, true);
														echo 'download Essex successful!'.PHP_EOL;
														// 保存首页
														file_put_contents(PROJECT_APP_DOWN.$web_name.'/'.$i.'/'.$type[$str].'_'.$p.'.html', $response->getBody());
													}
													if($sondom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'/'.$i.'/'.$type[$str].'_'.$p.'.html')))
													{	
														foreach ($sondom->find('#loader-content-'.$type[$str].' .grid__item') as $li) 
														{
															if($li->find('a'))
															{
																$lasturl = $base_url[$key].$li->find('a',0)->href;
																$lasttitle = $li->find('h3',0)->plaintext;
															    $temp = [
															    	'url' => $lasturl,
															    	'status' => 'wait',
															    	'md5_url' => md5($lasturl),
															    	'title' => $lasttitle,
															    	'des' => $str,
															    	'code'=>$ptitle,
															    	'code_url'=>$base_url[$key].$url,
															    	'model'=>$li->find('.info-box__item',0)->plaintext,
															    	'home'=>$li->find('.info-box__item',1)->plaintext,
															    	'web_name'=>$web_name
															    ];
															    // 入库
															    $empty = Capsule::table('info')->where('md5_url',md5($lasturl))->get()->isEmpty();
															    if($empty) Capsule::table('info')->insert($temp);
																
															}
														}
														$sondom->clear();

													}
												}
												
											}

										}else
										{
											foreach ($dom->find('#loader-content-'.$type[$str].' .grid__item') as $li) 
											{
												if($li->find('a'))
												{
													$lasturl = $base_url[$key].$li->find('a',0)->href;
													$lasttitle = $li->find('h3',0)->plaintext;
												    $temp = [
												    	'url' => $lasturl,
												    	'status' => 'wait',
												    	'md5_url' => md5($lasturl),
												    	'title' => $lasttitle,
												    	'des' => $str,
												    	'code'=>$ptitle,
												    	'code_url'=>$base_url[$key].$url,
												    	'model'=>$li->find('.info-box__item',0)->plaintext,
												    	'home'=>$li->find('.info-box__item',1)->plaintext,
												    	'web_name'=>$web_name
												    ];
												    // 入库
												    $empty = Capsule::table('info')->where('md5_url',md5($lasturl))->get()->isEmpty();
												    if($empty) Capsule::table('info')->insert($temp);
												}
												
											}
										}
									}
									$j++;
								}
								$dom->clear();
							}
						}
						$i++;
					}
				}
				if($key=='pgr')
				{
					foreach($dom->find('#ContentMain_lbSubjectArea option') as $tr)
					{
						$title_name = $tr->plaintext;
						$type = $tr->value;
						$getUrl = 'https://www1.essex.ac.uk/pgr/pgrsearchresults.aspx?st=subjectarea&q='.$type;
						if(!is_file(PROJECT_APP_DOWN.$web_name.'/'.$key.'/'.$type.'.html'))
						{
							// 解析页面
							$client = new Client();
							$response = $client->get($getUrl,['verify'=>false]);
							@mkdir(PROJECT_APP_DOWN.$web_name.'/'.$key, 0777, true);
							echo 'download Essex-pgr successful!'.PHP_EOL;
							// 保存首页
							file_put_contents(PROJECT_APP_DOWN.$web_name.'/'.$key.'/'.$type.'.html', $response->getBody());
						}
						if($sondom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'/'.$key.'/'.$type.'.html')))
						{
							foreach($sondom->find('.tableDefault tr') as $tr)
							{
								if($tr->find('a'))
								{
									$title = $tr->find('a',0)->plaintext;
									$url = $base_url[$key].$tr->find('a',0)->href;
									$temp = [
								    	'url' => $url,
								    	'status' => 'wait',
								    	'md5_url' => md5($url),
								    	'title' => $title,
								    	'des' =>$tr->find('td',3)->plaintext,
								    	'qualification'=>$tr->find('td',2)->plaintext,
								    	'code'=>$type,
								    	'code_url'=>$getUrl,
								    	'model'=>$tr->find('td',1)->plaintext,
								    	'home'=>$title_name,
								    	'web_name'=>$web_name
								    ];
								    Capsule::table('info')->insert($temp);
								}

							}
							$sondom->clear();
						}
					}

				}
				echo 'Essex analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
	}

	// Swansea
	public static function Swansea()
	{
		$arr = ['taught'=>'http://www.swansea.ac.uk/postgraduate/taught/','research'=>'http://www.swansea.ac.uk/postgraduate/research/'];
		$web_name = 'Swansea';
		$base_url = 'http://www.swansea.ac.uk';
		foreach ($arr as $key=> $value) 
		{
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(__DIR__.'/'.$web_name.'_'.$key.'.php')))
			{
				foreach($dom->find('.course--a-to-z--section-courses-course') as $tr)
				{
					if($tr->find('a') && $url = $tr->find('a',0)->href)
					{
						$title = $tr->find('a',0)->plaintext;
					    $temp = [
					    	'url' => $url,
					    	'status' => 'wait',
					    	'md5_url' => md5($url),
					    	'title' => $title,
					    	'des' => $key,
					    	'web_name'=>$web_name
					    ];
					    // 入库
					    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
					    if($empty) Capsule::table('info')->insert($temp);
					}
				}
				echo 'Swansea analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
	}

	//Loughborough_University
	public static function Loughborough_University()
	{
		$arr = ['masters'=>'http://www.lboro.ac.uk/study/postgraduate/masters-degrees/a-z/','unfunded'=>'http://www.lboro.ac.uk/study/postgraduate/research-degrees/unfunded/','funded'=>'http://www.lboro.ac.uk/study/postgraduate/research-degrees/funded/','departments'=>'http://www.lboro.ac.uk/study/postgraduate/research-degrees/research-departments/'];
		$web_name = 'Loughborough_University';
		$base_url = 'http://www.lboro.ac.uk';
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
				$response = $client->get($value,['verify'=>false]);
				@mkdir(PROJECT_APP_DOWN, 0777, true);
				echo 'download Loughborough_University successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				foreach($dom->find('.list--programmes li') as $tr)
				{
					if($tr->find('a') && $tr->find('a',0)->href)
					{
						$url = $base_url.$tr->find('a',0)->href;
						$title = $tr->find('a',0)->plaintext;
					    $temp = [
					    	'url' => $url,
					    	'status' => 'wait',
					    	'md5_url' => md5($url),
					    	'title' => $title,
					    	'des' => $key,
					    	'qualification'=>$tr->find('h2 em',0)?$tr->find('h2 em',0)->plaintext:'',
					    	'code'=>$base_url.$tr->find('h3 a',0)->plaintext,
					    	'code_url'=>$base_url.$tr->find('h3 a',0)->href,
					    	'web_name'=>$web_name
					    ];
					    // 入库
					    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
					    if($empty) Capsule::table('info')->insert($temp);
					}
				}
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
		echo 'Loughborough_University analyse completed!'.PHP_EOL;
	}

	// Goldsmiths
	public static function Goldsmiths()
	{
		$web_name = 'Goldsmiths';
		if(!is_file(PROJECT_APP_DOWN.$web_name.'.html'))
		{
			$web = 'https://www.gold.ac.uk/pg/a-z/';
			// 解析页面
			$client = new Client();
			$response = $client->get($web,['verify'=>false]);
			@mkdir(PROJECT_APP_DOWN, 0777, true);
			echo 'download Goldsmiths successful!'.PHP_EOL;
			// 保存首页
			file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		}
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		{
			$base_url='https://www.gold.ac.uk';
			foreach($dom->find('.grid-push',1)->find('article') as $li)
			{
				// 第二层
				if($li->find('h3 a'))
				{
					$url = $li->find('h3 a',0)->href;
					$title = $li->find('h3 a',0)->plaintext;
				    $temp = [
				    	'url' => $base_url.$url,
				    	'status' => 'wait',
				    	'md5_url' => md5($url),
				    	'title' => $title,
				    	'des' => $li->find('p',0)->plaintext,
				    	'model'=>$li->find('li',0)->plaintext,
				    	'web_name'=>$web_name
				    ];
				    // 入库
				    $empty = Capsule::table('info')->where('md5_url',md5($url))->get()->isEmpty();
				    if($empty) Capsule::table('info')->insert($temp);
					
				}
			}
			echo 'Goldsmiths analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}

	// Swansea
	public static function Stirling()
	{
		$arr = ['masters'=>'https://www.stir.ac.uk/postgraduate/programme-information/','degrees'=>'https://www.stir.ac.uk/postgraduate/research-degrees/'];
		$web_name = 'Stirling';
		$base_url = 'https://www.stir.ac.uk';
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
				$response = $client->get($value,['verify'=>false]);
				@mkdir(PROJECT_APP_DOWN, 0777, true);
				echo 'download Stirling successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				if($key=='masters')
				{
					foreach($dom->find('.az-list li') as $tr)
					{
						if($tr->find('a') && $url = $tr->find('a',0)->href)
						{
							$title = $tr->find('a',0)->plaintext;
						    $temp = [
						    	'url' => $base_url.$url,
						    	'status' => 'wait',
						    	'md5_url' => md5($base_url.$url),
						    	'title' => $title,
						    	'des' => $key,
						    	'web_name'=>$web_name
						    ];
						    // 入库
						    $empty = Capsule::table('info')->where('md5_url',md5($base_url.$url))->get()->isEmpty();
						    if($empty) Capsule::table('info')->insert($temp);
						}
					}
				}
				if($key=='degrees')
				{
					foreach($dom->find('.medium-pull-1 li') as $tr)
					{
						if($tr->find('a') && $url = $tr->find('a',0)->href)
						{
							if(!preg_match('/(http:\/\/)|(https:\/\/)/i', $url))
							{
								$url = $base_url.$url;
							}
							$title = $tr->find('a',0)->plaintext;
							if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'/'.$title.'.html'))
							{
								// 解析页面
								$client = new Client();
								$response = $client->get($url,['verify'=>false]);
								@mkdir(PROJECT_APP_DOWN.$web_name.'_'.$key, 0777, true);
								echo 'download degrees successful!'.PHP_EOL;
								// 保存首页
								file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'/'.$title.'.html', $response->getBody());
							}
							// // 创建dom对象
							if($sondom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'/'.$title.'.html')))
							{
								foreach($sondom->find('.medium-push-1 li') as $li)
								{
									if($li->find('a'))
									{
										$sonUrl = $url.$li->find('a',0)->href;
										$title = $tr->find('a',0)->plaintext;
									    $temp = [
									    	'url' => $sonUrl,
									    	'status' => 'wait',
									    	'md5_url' => md5($sonUrl),
									    	'title' => $title,
									    	'des' => $key,
									    	'web_name'=>$web_name
									    ];
									    // 入库
									    $empty = Capsule::table('info')->where('md5_url',md5($sonUrl))->get()->isEmpty();
									    if($empty) Capsule::table('info')->insert($temp);
									}
								}
								$sondom->clear();
							}
						}
					}
				}
				echo 'Stirling analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
	}

	// Kent
	public static function Kent()
	{
		$arr = ['masters'=>'https://www.kent.ac.uk/courses/postgraduate/search/','ma'=>'https://www.kent.ac.uk/courses/postgraduate/search/award/ma/','msc'=>'https://www.kent.ac.uk/courses/postgraduate/search/award/msc/','llm'=>'https://www.kent.ac.uk/courses/postgraduate/search/award/llm/','mphil'=>'https://www.kent.ac.uk/courses/postgraduate/search/award/mphil/'];
		$web_name = 'Kent';
		$base_url = 'https://www.kent.ac.uk';
		foreach ($arr as $key=> $value) 
		{
			if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html'))
			{
				// 解析页面
				$client = new Client();
				$response = $client->get($value,['verify'=>false]);
				@mkdir(PROJECT_APP_DOWN, 0777, true);
				echo 'download Kent successful!'.PHP_EOL;
				// 保存首页
				file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html', $response->getBody());
			}
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'.html')))
			{
				if($key=='masters')
				{
					foreach($dom->find('.az-list li') as $tr)
					{
						if($tr->find('a') && $url = $tr->find('a',0)->href)
						{
							$title = $tr->find('a',0)->plaintext;
						    $temp = [
						    	'url' => $base_url.$url,
						    	'status' => 'wait',
						    	'md5_url' => md5($base_url.$url),
						    	'title' => $title,
						    	'des' => $key,
						    	'web_name'=>$web_name
						    ];
						    // 入库
						    $empty = Capsule::table('info')->where('md5_url',md5($base_url.$url))->get()->isEmpty();
						    if($empty) Capsule::table('info')->insert($temp);
						}
					}
				}
				else
				{
					foreach($dom->find('.medium-pull-1 li') as $tr)
					{
						if($tr->find('a') && $url = $tr->find('a',0)->href)
						{
							if(!preg_match('/(http:\/\/)|(https:\/\/)/i', $url))
							{
								$url = $base_url.$url;
							}
							$title = $tr->find('a',0)->plaintext;
							if(!is_file(PROJECT_APP_DOWN.$web_name.'_'.$key.'/'.$title.'.html'))
							{
								// 解析页面
								$client = new Client();
								$response = $client->get($url,['verify'=>false]);
								@mkdir(PROJECT_APP_DOWN.$web_name.'_'.$key, 0777, true);
								echo 'download Kent successful!'.PHP_EOL;
								// 保存首页
								file_put_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'/'.$title.'.html', $response->getBody());
							}
							// // 创建dom对象
							if($sondom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'_'.$key.'/'.$title.'.html')))
							{
								foreach($sondom->find('.medium-push-1 li') as $li)
								{
									if($li->find('a'))
									{
										$sonUrl = $url.$li->find('a',0)->href;
										$title = $tr->find('a',0)->plaintext;
									    $temp = [
									    	'url' => $sonUrl,
									    	'status' => 'wait',
									    	'md5_url' => md5($sonUrl),
									    	'title' => $title,
									    	'des' => $key,
									    	'web_name'=>$web_name
									    ];
									    // 入库
									    $empty = Capsule::table('info')->where('md5_url',md5($sonUrl))->get()->isEmpty();
									    if($empty) Capsule::table('info')->insert($temp);
									}
								}
								$sondom->clear();
							}
						}
					}
				}
				echo 'Stirling analyse completed!'.PHP_EOL;
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
		}
	}

	// Bangor_University
	public static function Bangor_University()
	{
		$web_name = 'Bangor_University';
		if(!is_file(PROJECT_APP_DOWN.$web_name.'.html'))
		{
			$web = 'https://www.bangor.ac.uk/courses/postgraduate/';
			// 解析页面
			$client = new Client();
			$response = $client->get($web,['verify'=>false]);
			@mkdir(PROJECT_APP_DOWN, 0777, true);
			echo 'download Bangor_University successful!'.PHP_EOL;
			// 保存首页
			file_put_contents(PROJECT_APP_DOWN.$web_name.'.html', $response->getBody());
		}
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html(file_get_contents(PROJECT_APP_DOWN.$web_name.'.html')))
		{
			$base_url='https://www.bangor.ac.uk';
			foreach($dom->find('#course-browse option') as $li)
			{
				$val = $li->value;
				$title = $li->plaintext;
				if($val>0)
				{
					if(!is_file(PROJECT_APP_DOWN.$web_name.'/'.$val.'.html'))
					{
						$web = 'https://www.bangor.ac.uk/common/course-search/pg.php.en';
						// 解析页面
						$client = new Client();
						$response = $client->request('POST',$web, [
									'Content-Length'=>'14',
									'Cookie'=>"PHPSESSID=afnpvkj9vvt4h2pf53brind1j4; _ga=GA1.3.306510676.1521955602;_gid=GA1.3.1443050632.1521955602;_gali=course-browse; _gat=1",
									'Referer'=> 'https://localhost',
									'Origin'=>'https://www.bangor.ac.uk',
									'term'=>'',
								    'browse' => $val,
								    'verify'=>false
								]);
						@mkdir(PROJECT_APP_DOWN.$web_name, 0777, true);
						echo 'download Bangor_University successful!'.PHP_EOL;
						// 保存首页
						file_put_contents(PROJECT_APP_DOWN.$web_name.'/'.$val.'.html', $response->getBody());
					}
				}
			}
			echo 'diguo analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
	}

}
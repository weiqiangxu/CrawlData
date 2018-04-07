<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;


use Illuminate\Database\Schema\Blueprint;

/**
  * @author xu
  * @copyright 2018/01/29
  */
class twostep{


	public static function series()
	{
		// 下载
		Capsule::table('brand')->where('status','wait')->orderBy('id')->chunk(10,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'brand', 0777, true);
			// 并发请求
		    $guzzle = new guzzle();
		    $guzzle->poolRequest('brand',$datas);	    
		});

		// 解析
		Capsule::table('brand')->where('status','completed')->orderBy('id')->chunk(20,function($datas){

			$prefix = 'https://car.autohome.com.cn';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	$file = PROJECT_APP_DOWN.'brand/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		// 字符编码转换
					$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");
					$html = ltrim($html,'document.writeln("');
					$html = rtrim($html,'");');
					// dom解析
		    		if($dom = HtmlDomParser::str_get_html($html))
					{
						$prefix = 'https://car.autohome.com.cn';
						$subbrand = '';
						
						foreach ($dom->find('.current dl',0)->children as $children)
						{
							if($children->tag == 'dt')
							{
								$subbrand = trim($children->plaintext);
								continue;
							}
							if($children->tag == 'dd')
							{
								$url = $prefix.$children->find('a',0)->href;
								preg_match('/\d+/', $children->find('em',0)->plaintext, $match);
								// 存储
							    $temp = [
							    	'url' => $url,
							    	'status' => 'wait',
							    	'md5_url' => md5($url),
							    	'brand' => $data->brand,
							    	'subbrand' => $subbrand,
							    	'series' => trim(preg_replace('/\(\d+\)/','',$children->find('a',0)->plaintext)),
							    	'series_num' => (int)current($match),
							    ];
								$empty = Capsule::table('brand')->where('md5_url',md5($url))->get()->isEmpty();
								if($empty) Capsule::table('series')->insert($temp);
							}
						}
			            // 更改SQL语句
			            Capsule::table('brand')->where('id', $data->id)->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'brand '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});

	}
}
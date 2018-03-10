<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;

/**
  * 下载所有零件详情页面
  * @author xu
  * @copyright 2018/01/29
  */
class fourstep{

	// model
	public static function model()
	{
		// 下载所有的model页面
		Capsule::table('model_list')->where('status','wait')->orderBy('id')->chunk(10,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'model_list', 0777, true);
			// 并发请求
		    $guzzle = new guzzle();
		    $guzzle->poolRequest('model_list',$datas);
		});

		// 解析所有的model页面获取engine信息
		Capsule::table('model_list')->where('status','completed')->orderBy('id')->chunk(10,function($datas){

			$prefix = 'http://www.rockauto.com';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	$file = PROJECT_APP_DOWN.'model_list/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{

		    		// 字符编码转换
					$html = mb_convert_encoding(file_get_contents($file),"UTF-8", "gb2312");

		    		if($dom = HtmlDomParser::str_get_html($html))
					{
						// 车型入库												
						foreach ($dom->find('.interval01-list li .interval01-list-cars-infor') as $div)
						{

							$url = ltrim($div->find('p',0)->find('a',0)->href,'//');
							$model = $div->find('p',0)->find('a',0)->plaintext;

							// 存储
						    $temp = [
						    	'url' => $url,
						    	'status' => 'wait',
						    	'md5_url' => md5($url),
						    	'brand_one' => $data->brand_one,
						    	'brand_two' => $data->brand_two,
						    	'series' => $data->series,
						    	'model' => $model
						    ];
							$empty = Capsule::table('model')
								->where('md5_url',md5($url))
								->get()
								->isEmpty();
							if($empty)
							{
								Capsule::table('model')->insert($temp);					    	
							}
						}
	
						// 检测是否有下一页
						foreach($dom->find('.page-item-next',0))
						{
							echo $dom->find('.page-item-next',0)->tag;die;
						}


			            // 更改SQL语句
			            Capsule::table('model_list')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'model_list '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});


	}

}
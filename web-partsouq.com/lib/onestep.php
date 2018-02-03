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

		// url_brand表
		if(!Capsule::schema()->hasTable('url_brand'))
		{
			Capsule::schema()->create('url_brand', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->nullable()->unique();
			    $table->string('status')->nullable();
			});
			echo "table url_brand create\r\n";
		}



	}

	// 获取所有的 https://partsouq.com/en/catalog/genuine/locate?c=BMW
	public static function brand()
	{

		// 解析首页
		$prefix = 'https://partsouq.com/';
		$mineload = new mineload();
		$res = $mineload->curl_https($prefix);

		echo $res['html'];die;
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html($res['html']))
		{
			foreach($dom->find('item') as $article)
			{
				var_dump($article->outertext);die;
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
			echo 'series analyse completed!'."\r\n";
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
		else
		{
			exit('net error!');
		}


	}
}
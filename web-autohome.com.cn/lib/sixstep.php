<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;
use Illuminate\Database\Schema\Blueprint;

use Overtrue\Pinyin\Pinyin;

/**
  * 转移数据
  * @author xu
  * @copyright 2018/03/16
  */
class sixstep{

	// 建表
	public static function get()
	{	

		$empty = Capsule::table('raw_data')->where('status','wait')->get()->isEmpty();

		while (!$empty) {

			$datas = Capsule::table('raw_data')->where('status','wait')->limit(20)->get();
			// json文件是否存在
			if(!file_exists(PROJECT_APP_DOWN.'every_column.json')) 
				file_put_contents(PROJECT_APP_DOWN.'every_column.json', json_encode([['my_test','1']]));

			$every_column = json_decode(file_get_contents(PROJECT_APP_DOWN.'every_column.json'),true);

			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 当前所有车参数
		    	$json = json_decode($data->data,true);

		    	foreach ($json as $name => $v)
		    	{	
		    		// 数量
		    		$num = mb_strlen($v);

		    		// 名称存在且数量大于则删除原来的并加上大的
		    		foreach ($every_column as $kk => $vv) {
		    			// 如果存在且数量大于
		    			if(($name == $vv[0]) && ($num > $vv[1]) )
		    			{
		    				unset($every_column[$kk]);
		    				$every_column[] = [$name,$num];
		    			}
		    		}
		    		// 如果不存在与列之中
		    		$all_name = array_column($every_column,'0');
		    		

		    		if(!in_array($name,$all_name))
		    		{
		    			$every_column[] = [$name,$num];
		    		}

		    	}
		    	file_put_contents(PROJECT_APP_DOWN.'every_column.json', json_encode($every_column));
	            // 更改SQL语句
	            Capsule::table('raw_data')->where('id', $data->id)->update(['status' =>'readed']);
			    // 命令行执行时候不需要经过apache直接输出在窗口
	            echo 'raw_data '.$data->id.'.html'."  analyse successful!".PHP_EOL;
		    }

		    $empty = Capsule::table('raw_data')->where('status','wait')->get()->isEmpty();
			
		}

		// 根据获取的列和数量进行-建表
	    $every_column = json_decode(file_get_contents(PROJECT_APP_DOWN.'every_column.json'),true);	

	    $pinyin = new Pinyin(); 
	    // 建表语句
	    $create_table_str = '';
	    // 文字对拼音
	    $text_to_pinyin = array();

	    foreach ($every_column as $k => $v)
	    {
	    	$this_column = $pinyin->abbr($v[0]);

	    	$text_to_pinyin[$v[0]] = $this_column;
	    	
	    	$create_table_str .= '$table->char("'.$this_column.'", '.$v[1].')->nullable()->comment("'.$v[0].'");';
	    }
	   	// 将文字对拼音转存到json
	   	file_put_contents(PROJECT_APP_DOWN.'text_to_pinyin.json', json_encode($text_to_pinyin)); 

	   	// 输出建表语句
	    echo $create_table_str;
	    exit();

	}

	// 建表
	public static function initable()
	{
		// car
		if(!Capsule::schema()->hasTable('car'))
		{
			Capsule::schema()->create('car', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('brand')->nullable()->comment('品牌');
			    $table->string('subbrand')->nullable()->comment('子品牌');
			    $table->string('series')->nullable()->comment('车系');
			    $table->string('model')->nullable()->comment('车型').$create_table_str;
			 	// 在这里接上上面获取的所有字段以及最大数量的建表语句

			});
			echo "table car create".PHP_EOL;
		}
	}

	// 读取
	public static function move()
	{
		// 获取中文对应列
		$text_to_pinyin = json_decode(file_get_contents(PROJECT_APP_DOWN.'text_to_pinyin.json'),true);

		$empty = Capsule::table('raw_data')->where('status','readed')->get()->isEmpty();

		while (!$empty) {

			$datas = Capsule::table('raw_data')->where('status','readed')->limit(20)->get();

			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 拼接数据
				$temp = array(
					'brand' => $data->brand,
					'subbrand' => $data->subbrand,
					'series' => $data->series,
					'model' => $data->model,
					'md5_url' => $data->md5_url,
					'url' => $data->url,
				);
				// 拼接车型参数字段
				foreach (json_decode($data->data,true) as $k => $v) {
					$temp = array_merge($temp,[$text_to_pinyin[$k] => $v]);
				}
				// 入库
				$empty = Capsule::table('car')->where('md5_url',$data->md5_url)->get()->isEmpty();
				if($empty) Capsule::table('car')->insert($temp);
				// 更新状态
				Capsule::table('raw_data')->where('id', $data->id)->update(['status' =>'moved']);
				// 输出
				echo 'raw_data '.$data->id."  moved successful!".PHP_EOL;
		    }
		    // 校验是否为空
			$empty = Capsule::table('raw_data')->where('status','wait')->get()->isEmpty();
			
		}

	}

}
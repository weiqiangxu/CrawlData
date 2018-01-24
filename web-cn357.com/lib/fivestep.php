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
  * 清洗数据
  * @author xu
  * @copyright 2018/01/24
  */
class fivestep{

	public static function initable()
	{
		// 数据库迁移类对象
		$Schema = Capsule::connection('final_database')->getSchemaBuilder();
		
		if(!$Schema->hasTable('brand'))
		{
			// 如果不存在品牌表就创建这个数据表
			$Schema->create('brand', function (Blueprint $table) {
			    $table->increments('ul_id');
			    $table->string('ul_url');
			    $table->string('ul_status');
			    $table->string('ul_filename');
			    $table->string('ul_filepath');
			});
		}
		echo "init table successful!\r\n";
	}

	// 数据清洗
	public static function cleandata()
	{
		// chunk分块处理每100条数据进行清洗
		Capsule::table('raw_data')->orderBy('id')->chunk(1000,function($datas){
			// 日志操作类
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'fourstep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// $LibFile->WriteData($logFile, 4, $data->file_path.'/'.$data->id.'.html'.'解析完成！');
		    	var_dump($data);die;
		    }
		});
	}

}
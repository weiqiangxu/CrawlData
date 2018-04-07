<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;


use Illuminate\Database\Schema\Blueprint;


/**
  * 检测需要下载的批次并下载相应批次的列表页
  * @author xu
  * @copyright 2018/01/24
  */
class onestep{

	// 批次最大页码
	public static $pici=[];

	// 初始化所有数据表
	public static function initable()
	{
		// url_index表
		if(!Capsule::schema()->hasTable('url_index'))
		{
			Capsule::schema()->create('url_index', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->unique()->nullable();
			    $table->integer('num')->nullable();
			    $table->string('status')->nullable();
			});
			echo "table url_index create".PHP_EOL;
		}

		// url_list表
		if(!Capsule::schema()->hasTable('url_list'))
		{
			Capsule::schema()->create('url_list', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url')->unique()->nullable();
			    $table->string('status')->nullable();
			});
			echo "table url_list create".PHP_EOL;
		}

		// url_detail
		if(!Capsule::schema()->hasTable('url_detail'))
		{
			Capsule::schema()->create('url_detail', function (Blueprint $table){
			    $table->increments('id');
			    $table->string('url');
			    $table->string('route_url');
			    $table->string('status');
			});
			echo "table url_detail create".PHP_EOL;
		}

	}


	// 校验是否需要更新
	public static function judgeupdate()
	{
		$prefix = 'http://www.cn357.com/notice_';
		// 获取当前http://www.cn357.com/notice_list的所有批次号码
		$temp = file_get_contents("http://www.cn357.com/notice_list");
		// 创建dom对象
		$dom = HtmlDomParser::str_get_html($temp);
		// 获取最大批次 /notice_301
		$maxpici  = $dom->find(".lotList",0)->first_child()->href;
		// 正匹配获取数据
		preg_match('/notice_(\d+)/', $maxpici, $matche);
		// 最大批次号码为
		$maxPici = $matche[1];

		// 获取最大批次
		$data = Capsule::table('url_index')
            ->orderBy('num', 'desc')
            ->first();
        // 校验是否为空表
        if($data)
        {
        	$max = $data->num;

        }
        else
        {
        	$max = 1;
        }
        $temp = array();
        // 获取需要读取的批次
        for ($i=$max+1; $i<=$maxPici;$i++)
	    { 
	    	 // 存储进去所有的&body
		    $temp = [
		    	'url' => $prefix.$i,
		    	'status' => 'wait',
		    	'num' => $i
		    ];
		    $empty = Capsule::table('url_index')
		    	->where('url',$prefix.$i)
		    	->get()
		    	->isEmpty();
		    if($empty)
		    {
			    Capsule::table('url_index')->insert($temp);					    	
		    }
	    	echo "need to download pici :".$i.PHP_EOL;
	    }
	}

	// 读取最大页码初始化需要下载的列表页
	public static function initlist()
	{
		// 下载所有的http://www.cn357.com/notice_1页面
		Capsule::table('url_index')->where('status','wait')->orderBy('id')->chunk(20,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'url_index', 0777, true);

			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	$guzzle = new guzzle();
		    	$guzzle->down('url_index',$data);
		    }
		});

		// 分析获取最大页码
		Capsule::table('url_index')->where('status','completed')->orderBy('id')->chunk(20,function($datas){
			// 循环块级结果
		    foreach ($datas as $data)
		    {
				// 保存文件名
		    	$file = PROJECT_APP_DOWN.'url_index/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						if($dom->find(".nextprev",0))
						{
							// 最大页码
							$res = $dom->find(".nextprev",0)->prev_sibling()->innertext;
							for ($i=1; $i <= (int)$res; $i++)
							{ 
								$temp = [
									'url' => $data->url.'_'.$i,
									'status' => 'wait',
								];
							    $empty = Capsule::table('url_list')
							    	->where('url',$data->url.'_'.$i)
							    	->get()
							    	->isEmpty();
							    if($empty)
							    {
								    Capsule::table('url_list')->insert($temp);					    	
							    }
							}
						}
						else
						{
							$temp = [
									'url' => $data->url.'_1',
									'status' => 'wait',
								];
						    $empty = Capsule::table('url_list')
						    	->where('url',$data->url.'_1')
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
							    Capsule::table('url_list')->insert($temp);					    	
						    }
						}
						echo 'url_index '.$data->id.' analyse completed!'.PHP_EOL;
						// 清理内存防止内存泄漏
						$dom-> clear(); 
					}
				}
			}
		});
		echo "update url_list successful".PHP_EOL;
	}


	// 下载 列表数字-页码 的所有页面
	public static function loadList()
	{
		Capsule::table('url_list')->where('status','wait')->orderBy('id')->chunk(20,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'url_list', 0777, true);

			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	$guzzle = new guzzle();
		    	$guzzle->down('url_list',$data);
		    }
		});
	}
}
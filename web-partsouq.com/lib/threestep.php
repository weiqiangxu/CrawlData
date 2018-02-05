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
class threestep{

	public static function download()
	{
		// 下载所有的配件详情页面
		Capsule::table('url_pic')->where('status','wait')->orderBy('id')->chunk(20,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'url_pic', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'url_pic/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!file_exists($file))
		    	{
		    		$mineload = new mineload();
		    		$res = $mineload->curl_https($data->url);
		    		if($res['info']['http_code']== 200)
		    		{
		    			// 保存文件
			            file_put_contents($file,$res['html']);
			            // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'url_pic '.$data->id.'.html'." download successful!\r\n";
		    		}
		    	}
		    	if(file_exists($file))
		    	{
		            // 更改SQL语句
		            Capsule::table('url_pic')
				            ->where('id', $data->id)
				            ->update(['status' =>'completed']);
		    	}
		    }
		});
	}

}
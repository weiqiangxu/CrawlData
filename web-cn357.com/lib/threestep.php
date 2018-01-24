<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;


/**
  * 下载所有需要解析的详情页
  * @author xu
  * @copyright 2018/01/24
  */
class threestep{

	// 初始化列表页
	public static function download()
	{
		// chunk分块处理每100条数据
		Capsule::table('url_detail')->where('status','wait')->orderBy('id')->chunk(100,function($datas){
			$LibFile = new LibFile();
			// 记录第三步骤日志
			$logFile = PROJECT_APP_DOWN.'threestep.txt';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 保存文件名
		    	$path = PROJECT_APP_DOWN.'url_detail/'.$data->file_path;
		    	if(!file_exists($path) || !is_dir($path))
		    	{
		       		// 如果文件夹不存在则创建文件夹
		    		@mkdir($path, 0777, true);
		    	}
		    	// 页面文件名
		    	$file = $path.'/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!is_file($file))
		    	{
		    		// 调用下载器
			        $Gather = new Gather();

			        // 页面http地址
			        $Option = [
			            CURLOPT_URL => $data->company_url
			        ];
			        // 发送curl请求
			        $Result = $Gather->curlContentsByArr($Option);
			        // 判定返回结果
			        if (200 == $Result['info']['http_code'])
			        {
			        	// 保存文件
			            file_put_contents($file,$Result['results']);
			            // 命令行执行时候不需要经过apache直接输出在窗口
			            echo $data->file_path.'/'.$data->id.'.html'."  download successful!\r\n";
			            // 记录成功
			            $LibFile->WriteData($logFile, 4,$data->file_path.'/'.$data->id.'.html'.'下载完成！');
			            // 更改SQL语句
			            Capsule::table('url_detail')
					            ->where('id', $data->id)
					            ->update(['status' =>'completed']);
			        }
		    	}
		    }
		});
	}	
}
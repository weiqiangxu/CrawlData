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
  * @copyright 2018/01/24
  */
class onestep{

	// 批次最大页码
	public static $pici=[];
	// 初始化列表页
	public static function initlist()
	{
		$LibFile = new LibFile();
		// 记录第1步骤日志
		$logFile = PROJECT_APP_DOWN.'onestep.txt';
		// 检测数据库是否存在如果不存在就删除
		Capsule::schema()->dropIfExists('url_list');
		echo "url_list delete\r\n";
		Capsule::schema()->create('url_list', function (Blueprint $table) {
		    $table->increments('ul_id');
		    $table->string('ul_url');
		    $table->string('ul_status');
		    $table->string('ul_filename');
		    $table->string('ul_filepath');
		});
		echo "url_list create\r\n";
		// 获取十条待下载页面数据
		$prefix = 'http://www.cn357.com/notice';
		// 获取数组  批次=》最大页码
		$piciToPage = array();
		// 获取需要读取的批次的页码
		foreach (self::$pici as $v)
		{
			// 防止foreach跑的太快未获取到内容直接报prev_sibling none object
			$ch = curl_init();
			// 2. 设置选项，包括URL
			curl_setopt($ch,CURLOPT_URL,'http://www.cn357.com/notice_'.$v);
			// 设置获取到内容不直接输出到页面上
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			// 设置等待时间无限长，强制必须获取到内容
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT_MS,0);
			// CURLLOPT_HEADER设置为0表示不返回HTTP头部信息
			curl_setopt($ch,CURLOPT_HEADER,0);
			// 3. 执行并获取HTML文档内容
			$temp = curl_exec($ch);
			// 4. 释放curl句柄
			curl_close($ch);
			// 创建dom对象
			if($dom = HtmlDomParser::str_get_html($temp))
			{
				if($dom->find(".nextprev",0))
				{
					$res = $dom->find(".nextprev",0)->prev_sibling()->innertext;
					$piciToPage[$v] = (int)$res;
				}
				else
				{
					$piciToPage[$v] = 1;
				}
				$LibFile->WriteData($logFile, 4, '列表页 http://www.cn357.com/notice_'.$v.'分析获取最大页码完毕！');
				echo 'http://www.cn357.com/notice_'.$v.' analyse completed!'."\r\n";
				// 清理内存防止内存泄漏
				$dom-> clear(); 
			}
			else
			{
				$LibFile->WriteData($logFile, 4, '列表页 http://www.cn357.com/notice_'.$v.'分析获取最大页码失败！');
				echo 'http://www.cn357.com/notice_'.$v.' net error '."\r\n";
			}
		}
		// 获取需要下载的所有页码
		foreach ($piciToPage as $pici => $max)
		{
			for ($i=1; $i <= $max; $i++)
			{ 
				// 某一列表页url
				$data = array(
					'ul_url'=>$prefix.'_'.$pici.'_'.$i,
					'ul_status'=>0,
					'ul_filename'=>$pici.'_'.$i,
					'ul_filepath'=>$pici
				);
				// 插入记录
				Capsule::table('url_list')->insert($data);
			}
		}
		echo "update url_list successful\r\n";
		$LibFile->WriteData($logFile, 4, 'url_list 数据表更新完成！');
		echo 'url_list table update completed!'."\r\n";
		// 存储路径
		$sPath = PROJECT_APP_DOWN.'url_list';
		// 创建文件夹
		@mkdir($sPath, 0777, true);
		// 读取需要下载的列表页
		$aTable = Capsule::table('url_list')->get();
		// 闭包函数转为数组
		$aTable = $aTable->transform(function($aItem) {
		    return (array) $aItem;
		})->toArray();
		// 循环下载项
		foreach ($aTable as $sKey => $aVal)
		{
			echo $aVal['ul_filename'];
			// 存储文件名
		    $sFile = sprintf('%s/%s.html', $sPath, $aVal['ul_filename']);
		    
		    // 判定文件是否存在且为正常的文件
		    if (!is_file($sFile))
		    {
		        // 调用下载器
		        $oGather = new Gather();
		        // 页面http地址
		        $aOption = [
		            CURLOPT_URL => $aVal['ul_url'],
		        ];
		        // 发送curl请求
		        $aResult = $oGather->curlContentsByArr($aOption);
		        // 判定返回结果
		        if (200 == $aResult['info']['http_code'])
		        {
		            file_put_contents($sFile,$aResult['results']);
		            // 记录成功
			        $LibFile->WriteData($logFile, 4, '列表页 '.$aVal['ul_filename'].'下载完成！');
		        }
		    }
            echo "==ok\r\n";
		}
	}

	// 校验是否需要更新
	public static function judgeupdate()
	{
		// 获取当前http://www.cn357.com/notice_list的所有批次号码
		$temp = file_get_contents("http://www.cn357.com/notice_list");
		// 创建dom对象
		$dom = HtmlDomParser::str_get_html($temp);
		// 获取最大批次 /notice_301
		$maxpici  = $dom->find("#noticeList",0)->first_child()->href;
		// 正匹配获取数据
		preg_match('/notice_(\d+)/', $maxpici, $matche);
		// 最大批次号码为
		$maxPici = $matche[1];
		// 判定是否数据表存在
		if (Capsule::schema()->hasTable('url_list'))
		{
			// 获取最大批次
			$data = Capsule::table('url_list')
                ->orderBy('ul_id', 'desc')
                ->first();
            // 校验是否为空表
            if($data)
            {
            	$max = $data->ul_filepath;
            }
            else
            {
            	$max = 1;
            }
            $temp = array();
            // 获取需要读取的批次
            for ($i=$max+1; $i<=$maxPici;$i++)
		    { 
		    	$temp[] = $i;
		    	echo "need to download pici :".$i."\r\n";
		    }
		    if(!empty($temp))
		    {
				// 需要读取的批次 
				self::$pici = $temp;
				// 初始化要下载的列表页
				self::initlist();
		    }
		    else
		    {
		    	echo 'noting need to update!';
		    }
		}
		else
		{
			// 获取所有批次号
			$temp = array();
		    for ($i=2; $i<=$maxPici;$i++)
		    { 
		    	$temp[] = $i;
		    	echo "need to download pici :".$i."\r\n";
		    }
			// 需要读取的批次 
			self::$pici = $temp;
			// 初始化要下载的列表页
			self::initlist();
		}
	}

}
<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;

// 初始化待下载的页面地址表
class onestep{

	// 批次最大页码
	public static $pici=[];
	// 初始化列表页
	public static function initlist()
	{
		$LibFile = new LibFile();
		// 记录第1步骤日志
		$logFile = PROJECTPATH.'/down/onestep.txt';
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
			// 创建dom对象
			$dom = HtmlDomParser::file_get_html('http://www.cn357.com/notice_'.$v);
			$res = $dom->find(".nextprev",0)->prev_sibling()->innertext;
			$piciToPage[$v] = (int)$res;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
		// 获取需要下载的所有页码
		foreach ($piciToPage as $pici => $max)
		{
			for ($i=1; $i < $max; $i++)
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
		// 存储路径
		$sPath = PROJECTPATH.'down/url_list';
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
			        $LibFile->WriteData($logFile, 4, $aVal['ul_filename'].'下载完成！');
		        }
		    }
            echo "==ok\r\n";
		}
	}	
}
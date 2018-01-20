<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;

// 初始化待下载的页面地址表
class initable{

	// 批次最大页码
	public static $pici=[];
	// 数据库信息
	public static $database;

	// 初始化列表页
	public static function initlist()
	{
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
		// 下载所有的列表页
		

	}	
}
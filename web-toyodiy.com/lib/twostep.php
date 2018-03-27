<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;

/**
  * @author xu
  * @copyright 2018/01/29
  */
class twostep{

	// 车系=》车
	public static function car_part()
	{
		$guzzle = new guzzle();
		// 下载
		$empty = Capsule::table('car')->where('status','wait')->get()->isEmpty();
		@mkdir(PROJECT_APP_DOWN.'car', 0777, true);
		while(!$empty) {
			$datas = Capsule::table('car')->where('status','wait')->limit(20)->get();
		    $guzzle->poolRequest('car',$datas);
		    $empty = Capsule::table('car')->where('status','wait')->get()->isEmpty();
		}


		// 解析
		$empty = Capsule::table('car')->where('status','completed')->get()->isEmpty();
		while(!$empty) {
			$datas = Capsule::table('car')->where('status','completed')->limit(20)->get();
			foreach ($datas as $data) {
				$file = PROJECT_APP_DOWN.'car/'.$data->id.'.html';
				$prefix = 'http://www.toyodiy.com/parts/';
				if(!file_exists($file))
				{
					echo PROJECT_APP_DOWN.'car/'.$data->id.'.html not found!'.PHP_EOL;
					continue;
				}
				if($dom = HtmlDomParser::str_get_html(file_get_contents($file)))
				{
					if($dom->find('.diag-list',0))
					{
						foreach ($dom->find('.diag-list',0)->find('a') as $k => $a) {
							$str = explode(':', $a->plaintext);
							$temp = [
								'url' => $prefix.$a->href,
								'car_id' => $data->id,
								'status' => 'wait',
								'part_type' => htmlspecialchars_decode(trim($str[1])),
								'part_type_num' => trim($str[0]),
								'part_type_page' => 1
							];
							// 入库
							$empty = Capsule::table('car_part')->where('url',$prefix.$a->href)->get()->isEmpty();
							if($empty) Capsule::table('car_part')->insert($temp);
						}				    
					}
					else
					{
						echo 'car id '.$data->id.' data not found!'.PHP_EOL;
					}
				    // 更新状态
				    Capsule::table('car')->where('id', $data->id)->update(['status' =>'readed']);
					echo 'car '.$data->id.' analyse completed!'.PHP_EOL;
					// 清理内存防止内存泄漏
					$dom-> clear(); 
				}
			}
		    $empty = Capsule::table('car')->where('status','completed')->get()->isEmpty();
		}
	}
}
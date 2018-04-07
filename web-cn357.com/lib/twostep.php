<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;


use Illuminate\Database\Schema\Blueprint;


/**
  * 解析所有列表页获取需要下载的详情页
  * @author xu
  * @copyright 2018/01/24
  */
class twostep{

	// 初始化表并解析入库
	public static function initdetail()
	{
		// 下载所有的http://www.cn357.com/notice_1页面
		Capsule::table('url_list')->where('status','completed')->orderBy('id')->chunk(20,function($datas){
			// 循环块级结果
		    foreach ($datas as $data)
		    {
				// 保存文件名
		    	$file = PROJECT_APP_DOWN.'url_list/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
					$html = file_get_contents($file);
					// 创建dom对象
					if($dom = HtmlDomParser::str_get_html($html))
					{
						// 获取所有的详情页下载链接
						$articles = array();
						foreach($dom->find('.listTable tr') as $article) {
							if($article->find('a',0))
							{
						    	$articles[] = $article->find('a',0)->href;
							}
						}
						array_unique($articles);
						// 入库获取到的当前页面的详情页信息
						foreach ($articles as $v)
						{
							// 某一列表页url
							$temp = array(
								// 保存路径批次+页码
								'route_url'=>$data->url,
								// 页面地址
								'url'=>'http://www.cn357.com'.$v,
								'status'=>'wait'
							);
							// 插入记录
							Capsule::table('url_detail')->insert($temp);
						}
						// 清理内存防止内存泄漏
						$dom-> clear();
						// 记录成功
						echo "url_list".$data->id." analyse ok!".PHP_EOL;
					}
					// 将状态设置为readed
					Capsule::table('url_list')
			            ->where('id', $data->id)
			            ->update(['status' => 'readed']);
		    	}
		    }
		});
	}	
}
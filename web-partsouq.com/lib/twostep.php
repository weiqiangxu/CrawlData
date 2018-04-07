<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;


use Illuminate\Database\Schema\Blueprint;

/**
  * @author xu
  * @copyright 2018/01/29
  */
class twostep{

	// 车系=》车
	public static function market()
	{


		// 获取所有的车连接
		Capsule::table('url_market')->where('status','completed')->orderBy('id')->chunk(5,function($datas){
			$prefix ='https://partsouq.com';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	// 保存文件名
		    	$file = PROJECT_APP_DOWN.'url_market/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 是否有筛选框
						if($dom->find("#model-filter-form",0))
						{
							// 有筛选框
							// 校验是否有 101 shown字眼，如果有的话将该页面标记为last
							if($dom->find("#content .container h3",0))
							{	
								// 获取数字
								preg_match("/\d+/", $dom->find("#content .container h3",0)->innertext,$num);
								echo 'url_market '.$data->id.' num is '.current($num).PHP_EOL;
								if(current($num) < 100 && current($num)>0)
								{
									// 此时已经是最终页面设置为last
									Capsule::table('url_market')
							            ->where('id', $data->id)
							            ->update(['status' => 'last']);
							        // 退出当前循环
							        continue;
								}
							}
							// 此时还不是最终链接，只能拼接下一级的下拉框获取新的url
							// 获取第一个下拉框选项拼接url的所有结果集 
							if(!$dom->find('#model-filter-form .cat_flt_',$data->level))
							{
								// 标记为失效页面
								Capsule::table('url_market')
						            ->where('id', $data->id)
						            ->update(['status' => 'non-object']);
						        echo 'url_market non-object '.$data->id.PHP_EOL; 
						        continue;
							}
							$name = $dom->find('#model-filter-form .cat_flt_',$data->level)->name;
							$temp = array();
							foreach ($dom->find('#model-filter-form .cat_flt_',$data->level)->find("option") as $value)
							{
								$temp_url = $data->url.'&'.$name.'='.urlencode(html_entity_decode(unicode_decode($value->value)));
								if($value->value!="")
								{
									$temp = [
												'status' => 'wait' ,
												'url' => $temp_url,
												'md5_url' => md5($temp_url),
												'level' => $data->level+1
											];
									// print_r($temp);die;
									$empty = Capsule::table('url_market')
								    	->where('md5_url',md5($temp_url))
								    	->get()
								    	->isEmpty();
								    if($empty)
								    {
									    Capsule::table('url_market')->insert($temp);					    	
								    }
								}
							}
							// 标记为失效页面
							Capsule::table('url_market')
					            ->where('id', $data->id)
					            ->update(['status' => 'Invalid']);
						}
						else
						{
							// 没有下拉选项不做处理，此刻已经显示所有的car链接
							// 将状态设置为last
							Capsule::table('url_market')
					            ->where('id', $data->id)
					            ->update(['status' => 'last']);
						}
					}
		    	}
		    	echo 'url_market '.$data->id.' analyse! '.PHP_EOL;
		    }
		});

		// 获取需要下载的页面
		$wait = Capsule::table('url_market')
            ->where('status', 'wait')
           	->count();
        echo "still have item need to download ,sum : ".$wait."\r\n";

	// 下载所有的market页面
	Capsule::table('url_market')->where('status','wait')->orderBy('id')->chunk(3,function($datas){
		// 创建文件夹
		@mkdir(PROJECT_APP_DOWN.'url_market', 0777, true);
		// 并发请求
	    $guzzle = new guzzle();
	    $guzzle->poolRequest('url_market',$datas);
	    sleep(3);
	    
	});
	}
}
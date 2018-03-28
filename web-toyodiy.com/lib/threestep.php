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
class threestep{

	// 车系=》车
	public static function part_detail()
	{
		$guzzle = new guzzle();
		// 下载
		$empty = Capsule::table('car_part')->where('status','wait')->get()->isEmpty();
		@mkdir(PROJECT_APP_DOWN.'car_part', 0777, true);
		while(!$empty) {
			$datas = Capsule::table('car_part')->where('status','wait')->limit(100)->get();
		    $guzzle->poolRequest('car_part',$datas);
		    $empty = Capsule::table('car_part')->where('status','wait')->get()->isEmpty();
		}

		// 解析
		$empty = Capsule::table('car_part')->where('status','completed')->get()->isEmpty();
		$prefix = 'http://www.toyodiy.com/parts/';
		while(!$empty) {
			$datas = Capsule::table('car_part')->where('status','completed')->limit(5)->get();
			foreach ($datas as $data) {

				$file = PROJECT_APP_DOWN.'car_part/'.$data->id.'.html';

				if(!file_exists($file))
				{
					echo PROJECT_APP_DOWN.'car_part/'.$data->id.'.html not found!'.PHP_EOL;
					Capsule::table('car_part')->where('id', $data->id)->update(['status' =>'notfound']);
					continue;
				}
				if($dom = HtmlDomParser::str_get_html(file_get_contents($file)))
				{
					// 下一页
					if($dom->find('.phdr',0)){
						if($dom->find('.phdr',0)->find('a',0))
						{
							$temp = array(
								'url' => $prefix.$dom->find('.phdr',0)->find('a',0)->href,
								'car_id' => $data->car_id,
								'status' => 'wait',
								'part_type' => $data->part_type,
								'part_type_num' => $data->part_type_num,
								'part_type_page' => $data->part_type_page+1,
							);
							// 入库
							$empty = Capsule::table('car_part')->where('url',$prefix.$dom->find('.phdr',0)->find('a',0)->href)->get()->isEmpty();
							if($empty) Capsule::table('car_part')->insert($temp);
						}
					}
					// 入库
					if($dom->find('#t2',0))
					{
						foreach ($dom->find('#t2',0)->find('.h') as $k => $v) {
							$key = $v->find('td',0)->plaintext;
							$title = $v->find('td',1)->plaintext;
							$des = array();
							
							if($v->next_sibling() && ($v->next_sibling()->tag == 'tr'))
							{
								$class = $v->next_sibling()->getAttribute('class');
								if(!$class)
								{
									foreach ($v->next_sibling()->find('td') as $kk => $vv) {
										$des[] = trim($vv->plaintext);
									}

									// 两行描述情况
									if($v->next_sibling()->next_sibling() && ($v->next_sibling()->next_sibling()->tag == 'tr'))
									{
										$class = $v->next_sibling()->next_sibling()->getAttribute('class');
										if(!$class)
										{
											$des[] = ';';
											foreach ($v->next_sibling()->next_sibling()->find('td') as $kk => $vv) {
												$des[] = trim($vv->plaintext);
											}

											// 三行描述情况
											if($v->next_sibling()->next_sibling()->next_sibling() && ($v->next_sibling()->next_sibling()->next_sibling()->tag == 'tr'))
											{
												$class = $v->next_sibling()->next_sibling()->next_sibling()->getAttribute('class');
												if(!$class)
												{
													$des[] = ';';
													foreach ($v->next_sibling()->next_sibling()->next_sibling()->find('td') as $kk => $vv) {
														$des[] = trim($vv->plaintext);
													}
													// 四行描述情况
													if($v->next_sibling()->next_sibling()->next_sibling()->next_sibling() && ($v->next_sibling()->next_sibling()->next_sibling()->next_sibling()->tag == 'tr'))
													{
														$class = $v->next_sibling()->next_sibling()->next_sibling()->next_sibling()->getAttribute('class');
														if(!$class)
														{
															$des[] = ';';
															foreach ($v->next_sibling()->next_sibling()->next_sibling()->next_sibling()->find('td') as $kk => $vv) {
																$des[] = trim($vv->plaintext);
															}

															// 五行描述情况
															if($v->next_sibling()->next_sibling()->next_sibling()->next_sibling()->next_sibling() && ($v->next_sibling()->next_sibling()->next_sibling()->next_sibling()->next_sibling()->tag == 'tr'))
															{
																$class = $v->next_sibling()->next_sibling()->next_sibling()->next_sibling()->next_sibling()->getAttribute('class');
																if(!$class)
																{
																	$des[] = ';';
																	foreach ($v->next_sibling()->next_sibling()->next_sibling()->next_sibling()->next_sibling()->find('td') as $kk => $vv) {
																		$des[] = trim($vv->plaintext);
																	}
																}
															}



														}
													}
												}
											}
										}

									}
								}
								else
								{
									echo 'car_part id '.$data->id.' line '.$k.' des not found!'.PHP_EOL;
								}

							}
							else
							{
								echo 'car_part id '.$data->id.' line '.$k.' des not found!'.PHP_EOL;
							}

							$temp = [
								'car_id' => $data->car_id,
								'url' => $data->url,
								'part_type' => $data->part_type,
								'part_type_num' => $data->part_type_num,
								'part_type_page' => $data->part_type_page,
								'part_detail_key' => $key,
								'part_detail_title' => $title,
								'part_detail_des' => implode(' ', array_filter(array_map('htmlspecialchars_decode',$des))),
							];
							// 入库
							Capsule::table('part_detail')->insert($temp);
						}
					}
					else
					{
						echo 'car_part id '.$data->id.' data not found!'.PHP_EOL;
					}
				    // 更新状态
				    Capsule::table('car_part')->where('id', $data->id)->update(['status' =>'readed']);
					echo 'car_part '.$data->id.' analyse completed!'.PHP_EOL;
					// 清理内存防止内存泄漏
					$dom-> clear(); 
				}
			}
		    $empty = Capsule::table('car_part')->where('status','completed')->get()->isEmpty();
		}
	}
}
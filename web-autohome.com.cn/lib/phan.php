<?php

// 多线程分析
class phan extends Thread {

    private $data = '';

 
    public function __construct($data){
        $this->data = $data;
    }

    public function run()
	{
		$database = [
		    'driver' => 'mysql',
		    'host' => 'localhost',
		    'database' => 'information_schema',
		    'username' => 'root',
		    'password' => '123456',
		    'charset' => 'utf8',
		    'collation' => 'utf8_unicode_ci',
		    'prefix' => ''
		];

		$data = $this->data;
		// 连接数据库
		$capsule = new Illuminate\Database\Capsule\Manager();
		$dbname = 'temp_autohome_'.date("Ym",time());
		$database = array_merge($database, ['database' => $dbname]);
		$capsule->addConnection($database);
    	$Capsule = $capsule->getConnection();
    	// 解析
    	$file = PROJECT_APP_DOWN.'model_detail/'.$data->id.'.html';
    	$res = $Capsule->table('model_detail')->where('id', $data->id)->get();
		//获取输出className的script代码
		preg_match_all('/<script>(.*?)<\/script>/s', file_get_contents($file), $matches);
		echo 'running phantomjs '.PHP_EOL;
		$class = array();
		$console = '$InsertRule$($index$, $item$){ console.log("\""+$GetClassName$($index$)+"\":\""+$item$+"\",");';

		// 执行四个JavaScript方法
		foreach ($matches[1] as $v)
		{
			if(!strpos($v, 'InsertRule')) continue;
			// 更改脚本
			file_put_contents(PROJECT_APP_DOWN.$data->id.'.js',preg_replace('/\$InsertRule\$\s+\(\$index\$,\s+\$item\$\)\s*{/',$console,$v).' phantom.exit();');
			exec(APP_PATH.'/bin/phantomjs '.PROJECT_APP_DOWN.$data->id.'.js > '.PROJECT_APP_DOWN.$data->id.'.txt');
			// 获取JavaScript执行结果
			$res = json_decode('{'.preg_replace('/,\s+$/', ' ', file_get_contents(PROJECT_APP_DOWN.$data->id.'.txt')).'}',true);
			// 去除类名的.
			foreach ($res as $k => $v) { $class[ltrim($k,'.')] = $v; }
			// 删除文件
			unlink(PROJECT_APP_DOWN.$data->id.'.js');
			unlink(PROJECT_APP_DOWN.$data->id.'.txt');
		}
		
		// config
		preg_match_all('/var\s*config\s*=(.*?});/', file_get_contents($file), $matches);
		$config = json_decode(current($matches[1]),true);
		$newConfig = array();
		foreach ($config['result']['paramtypeitems'] as $k => $v)
		{
			foreach ($v['paramitems'] as $kk => $vv)
			{
				$newConfig[] = array(
					'name' => $vv['name'],
					'lable' => $v['name'],
					'value' => $vv['valueitems'][0]['value']
				);
			}
		}
		// 替换
		$config = array();
		foreach ($newConfig as $k => $v) {
			$name = $v['name'];
			$value = $v['value'];
			// 替换
			foreach ($class as $kk => $vv) {
				$name = preg_replace("/<span\s*class='".$kk."'><\/span>/",$vv,$name);
				$value = preg_replace("/<span\s*class='".$kk."'><\/span>/",$vv,$value);
			}
			$config[] = array(
				'name' => $name,
				'lable' => $v['lable'],
				'value' => $value
			); 
		}


		// option
		preg_match_all('/var\s*option\s*=(.*?});/', file_get_contents($file), $matches);
		$option = json_decode(current($matches[1]),true);
		$newOption = array();
		foreach ($option['result']['configtypeitems'] as $k => $v)
		{
			foreach ($v['configitems'] as $kk => $vv) {
				$newOption[] = array(
					'name' => $vv['name'],
					'lable' => $v['name'],
					'value' => $vv['valueitems'][0]['value']
				);
			}
		}
		// 替换
		$option = array();
		foreach ($newOption as $k => $v) {
			$name = $v['name'];
			$value = $v['value'];
			// 替换
			foreach ($class as $kk => $vv) {
				$name = preg_replace("/<span\s*class='".$kk."'><\/span>/",$vv,$name);
				$value = preg_replace("/<span\s*class='".$kk."'><\/span>/",$vv,$value);
			}
			$option[] = array(
				'name' => $name,
				'lable' => $v['lable'],
				'value' => $value
			); 
		}


		// color
		preg_match_all('/var\s*color\s*=(.*?});/', file_get_contents($file), $matches);
		$color = json_decode(current($matches[1]),true);
		$newColor = array();
		if(isset($color['result']['specitems'][0]['coloritems']))
		{
			$str = implode(',',array_column($color['result']['specitems'][0]['coloritems'], 'name'));
			$newColor[] = ['name' => '外观颜色','lable' => '空调/冰箱','value' => $str];
		}
		// innerColor
		preg_match_all('/var\s*innerColor\s*=(.*?});/', file_get_contents($file), $matches);
		$innerColor = json_decode(current($matches[1]),true);
		$newInnerColor = array();
		if(isset($innerColor['result']['specitems'][0]['coloritems']))
		{
			$str = implode(',',array_column($innerColor['result']['specitems'][0]['coloritems'],'name')) ;
			$newInnerColor[] = ['name' => '内饰颜色','lable' => '空调/冰箱','value' => $str];
		}

		// 拼接所有数组
		$test = array_merge($config,$option,$newColor,$newInnerColor);

		// 存储于参数表中
		$insertData = array();
		foreach ($test as $v) {
			$insertData[] = array(
				'model_detail_id' => $data->id,
				'pram_lable' => $v['lable'],
				'pram_key' => $v['name'],
				'pram_val' => $v['value']
			);
		}
		// 插入数据库 param
		$empty = $Capsule->table('param')->where('model_detail_id',$data->id)->get()->isEmpty();
		if($empty) $Capsule->table('param')->insert($insertData);
		// 更新
		$Capsule->table('model_detail')->where('id', $data->id)->update(['status' =>'readed']);
		// 命令行执行时候不需要经过apache直接输出在窗口
		echo 'model_detail '.$data->id.'.html'."  analyse successful!".PHP_EOL; 
	}
}
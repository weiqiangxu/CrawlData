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

		// 用于存储原始数据数据的数据库
		$database = array_merge($database, ['database' => $dbname]);

		$capsule->addConnection($database);



    	// 存储文件
    	$file = PROJECT_APP_DOWN.'model_detail/'.$data->id.'.html';

    	$Capsule = $capsule->getConnection();


    	$res = $Capsule->table('model_detail')->where('id', $data->id)->get();

    	// 更新为正在读取
    	$Capsule->table('model_detail')->where('id', $data->id)->update(['reading' => 1]);

		//获取输出className的script代码
		preg_match_all('/<script>(.*?)<\/script>/s', file_get_contents($file), $matches);
		
		echo 'running phantomjs '.PHP_EOL;
		$class = array();
		$console = '$InsertRule$($index$, $item$){ console.log("\""+$GetClassName$($index$)+"\":\""+$item$+"\",");';
		// 循环每一段JavaScript代码并执行并获取结果
		foreach ($matches[1] as $v)
		{
			if(!strpos($v, 'InsertRule')) continue;
			// 加入console
			$str = preg_replace('/\$InsertRule\$\s+\(\$index\$,\s+\$item\$\)\s*{/',$console,$v);
			// 加入exit
			file_put_contents(PROJECT_APP_DOWN.$data->id.'.js', $str.' phantom.exit();');
			// 命令执行
			exec(APP_PATH.'/bin/phantomjs '.PROJECT_APP_DOWN.$data->id.'.js > '.PROJECT_APP_DOWN.$data->id.'.txt', $out, $status);
			// 读取文件并拼接为json
			$res = json_decode('{'.preg_replace('/,\s+$/', ' ', file_get_contents(PROJECT_APP_DOWN.$data->id.'.txt')).'}',true);
			// 去除类名的.
			foreach ($res as $k => $v) { $class[ltrim($k,'.')] = $v; }
		}
		// 删除文件
		unlink(PROJECT_APP_DOWN.$data->id.'.js');
		unlink(PROJECT_APP_DOWN.$data->id.'.txt');

		echo 'catch class'.PHP_EOL;
		// config
		preg_match_all('/var\s*config\s*=(.*?});/', file_get_contents($file), $matches);
		$config = json_decode(current($matches[1]),true);
		$newConfig = array();
		foreach ($config['result']['paramtypeitems'] as $k => $v)
		{
			foreach ($v['paramitems'] as $kk => $vv)
			{
				$newConfig[$vv['name']] = $vv['valueitems'][0]['value'];
			}
		}

		// 替换
		$config = array();
		foreach ($newConfig as $k => $v) {
			$kkk = '';
			$vvv = '';
			foreach ($class as $kk => $vv) {
				// 分别替换健名和键值对应的类名
				if($kkk!='') $k=$kkk;if($vvv!='') $v=$vvv;
				$kkk = preg_replace("/<span\s*class='".$kk."'><\/span>/",$vv,$k);
				$vvv = preg_replace("/<span\s*class='".$kk."'><\/span>/",$vv,$v);
			}
			$config[$kkk] = $vvv; 
		}

		echo 'catch config'.PHP_EOL;
		// option
		preg_match_all('/var\s*option\s*=(.*?});/', file_get_contents($file), $matches);
		$option = json_decode(current($matches[1]),true);
		$newOption = array();
		foreach ($option['result']['configtypeitems'] as $k => $v)
		{
			foreach ($v['configitems'] as $kk => $vv) {
				$newOption[$vv['name']] = $vv['valueitems'][0]['value'];
			}
		}
		// 替换
		$option = array();
		foreach ($newOption as $k => $v) {
			$kkk = '';
			$vvv = '';
			foreach ($class as $kk => $vv) {
				// 分别替换健名和键值对应的类名
				if($kkk!='') $k=$kkk;if($vvv!='') $v=$vvv;
				$kkk = preg_replace("/<span\s*class='".$kk."'><\/span>/",$vv,$k);
				$vvv = preg_replace("/<span\s*class='".$kk."'><\/span>/",$vv,$v);
			}
			$option[$kkk] = $vvv; 
		}
		echo 'catch option'.PHP_EOL;
		// color
		preg_match_all('/var\s*color\s*=(.*?});/', file_get_contents($file), $matches);
		$color = json_decode(current($matches[1]),true);
		$newColor = array();
		if(isset($color['result']['specitems'][0]['coloritems']))
		{
			$newColor = implode(',',array_column($color['result']['specitems'][0]['coloritems'], 'name'));
			$newColor = ['外观颜色'=>$newColor];
		}
		// innerColor
		preg_match_all('/var\s*innerColor\s*=(.*?});/', file_get_contents($file), $matches);
		$innerColor = json_decode(current($matches[1]),true);
		$newInnerColor = array();
		if(isset($innerColor['result']['specitems'][0]['coloritems']))
		{
			$newInnerColor = implode(',',array_column($innerColor['result']['specitems'][0]['coloritems'],'name')) ;
			$newInnerColor = ['内饰颜色' => $newInnerColor]; 
		}

		// 拼接所有数组
		$test = array_merge($config,$option,$newColor,$newInnerColor);

		// 先存储于数据库之中-转json
		$temp = array();

		$temp = array(
			'brand' => $data->brand,
			'subbrand' => $data->subbrand,
			'series' => $data->series,
			'model' => $data->model,
			'md5_url' => $data->md5_url,
			'url' => $data->url,
			'status' => 'wait',
			'data' => json_encode($test)
		);
		// raw_data
		$empty = $Capsule->table('raw_data')->where('md5_url',$data->md5_url)->get()->isEmpty();
		if($empty) $car_id = $Capsule->table('raw_data')->insert($temp);
		// 更新状态
		$Capsule->table('model_detail')->where('id', $data->id)->update(['status' =>'readed']);
		// 命令行执行时候不需要经过apache直接输出在窗口
		echo 'model_detail '.$data->id.'.html'."  analyse successful!".PHP_EOL; 
	}
}
<?php

// 加载composer资源库
require_once('../resources/autoload.php');
// 初始化数据库配置
require_once('./lib/config.php');
// 加载自己项目资源库
require_once('./lib/fourstep.php');
// 路径处理类
require_once('./lib/LibDir.php');
// 文件处理类
require_once('./lib/LibFile.php');


// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

// 初始化数据表
// 检测数据库是否存在如果不存在就删除
Capsule::schema()->dropIfExists('raw_data');
echo "raw_data delete\r\n";
Capsule::schema()->create('raw_data', function (Blueprint $table) {
    $table->increments('id');
    // 批次路径
    $table->string('pclj')->comment('批次路径');			
	// 页面地址
	$table->string('ymdz')->comment('页面地址');
	// 公告型号
	$table->string('ggxh')->nullable()->comment('公告型号');
	// 公告批次
	$table->string('ggpc')->nullable()->comment('公告批次');
	// 品牌
	$table->string('pp')->nullable()->comment('品牌');
	// 类型
	$table->string('lx')->nullable()->comment('类型');
	// 额定质量
	$table->string('edzl')->nullable()->comment('额定质量');
	// 总质量
	$table->string('zzl')->nullable()->comment('总质量');
	// 装备质量
	$table->string('zbzl')->nullable()->comment('装备质量');
	// 燃料种类
	$table->string('rlzl')->nullable()->comment('燃料种类');
	// 排放依据标准
	$table->string('pfyjbz')->nullable()->comment('排放依据标准');
	// 轴数
	$table->string('zs')->nullable()->comment('轴数');
	// 轴距
	$table->string('zj')->nullable()->comment('轴距');
	// 轴荷
	$table->string('zh')->nullable()->comment('轴荷');
	// 弹簧片数
	$table->string('thps')->nullable()->comment('弹簧片数');
	// 轮胎数
	$table->string('lts')->nullable()->comment('轮胎数');
	// 轮胎规格
	$table->string('ltgg')->nullable()->comment('轮胎规格');
	// 接近离去角
	$table->string('jjlqj')->nullable()->comment('接近离去角');
	// 前悬后悬
	$table->string('qxhx')->nullable()->comment('前悬后悬');
	// 前轮距
	$table->string('qlj')->nullable()->comment('前轮距');
	// 后轮距
	$table->string('hlj')->nullable()->comment('后轮距');
	// 识别代号
	$table->text('sbdh')->nullable()->comment('识别代号');
	// 整车长
	$table->string('zcz')->nullable()->comment('整车长');
	// 整车宽
	$table->string('zck')->nullable()->comment('整车宽');
	// 整车高
	$table->string('zcg')->nullable()->comment('整车高');
	// 货箱长
	$table->string('hxz')->nullable()->comment('货箱长');
	// 货箱宽
	$table->string('hxk')->nullable()->comment('货箱宽');
	// 货箱高
	$table->string('hxg')->nullable()->comment('货箱高');
	// 最高车速
	$table->string('zgcs')->nullable()->comment('最高车速');
	// 额定载客
	$table->string('edzk')->nullable()->comment('额定载客');
	// 驾驶数准乘人数
	$table->string('jsszcrs')->nullable()->comment('驾驶数准乘人数');
	// 转向形式
	$table->string('zxxs')->nullable()->comment('转向形式');
	// 准拖车总质量
	$table->string('ztgczzl')->nullable()->comment('准拖车总质量');
	// 载质量利用系数
	$table->string('zzllyxs')->nullable()->comment('载质量利用系数');
	// 半挂车鞍座最大承载质量
	$table->string('bgcazzdczzl')->nullable()->comment('半挂车鞍座最大承载质量');
	// 企业名称
	$table->string('qymc')->nullable()->comment('企业名称');
	// 企业地址
	$table->text('qydz')->nullable()->comment('企业地址');
	// 电话号码
	$table->string('dhhm')->nullable()->comment('电话号码');
	// 传真号码
	$table->string('czhm')->nullable()->comment('传真号码');
	// 邮政编码
	$table->string('yzbm')->nullable()->comment('邮政编码');
	// 底盘1
	$table->string('dp1')->nullable()->comment('底盘1');
	// 底盘2
	$table->string('dp2')->nullable()->comment('底盘2');
	// 底盘3
	$table->string('dp3')->nullable()->comment('底盘3');
	// 底盘4
	$table->string('dp4')->nullable()->comment('底盘4');
	// 发送机型号
	$table->text('fdjxh')->nullable()->comment('发送机型号');
	// 发动机生产企业
	$table->text('fdjscqy')->nullable()->comment('发动机生产企业');
	// 发动机商标
	$table->string('fdjsb')->nullable()->comment('发动机商标');
	// 排量
	$table->text('pl')->nullable()->comment('排量');
	// 功率
	$table->text('gl')->nullable()->comment('功率');
	// 备注
	$table->text('bz')->nullable()->comment('备注');
});
echo "raw_data create\r\n";


// 开1000个进程解析数据

for ($i=1; $i <= 1000; $i++)
{ 
	$fourstep = new fourstep();
	// 解析所有文件并储存原始数据raw_data
	$fourstep->analyse();
	
}

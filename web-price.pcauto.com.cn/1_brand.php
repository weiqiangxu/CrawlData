<?php
require 'config.php';

use Huluo\Extend\Gather;
use Sunra\PhpSimple\HtmlDomParser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

// 存储路径
$sPath = sprintf('%s/%s/brand', APP_DOWN, $database['database']);
@mkdir($sPath, 0777, true);

$sFile = sprintf('%s/brand.html', $sPath);
if (!is_file($sFile))
{
    $oGather = new Gather();
    $aOption = [
        CURLOPT_URL => 'http://price.pcauto.com.cn/index/js/5_5/treedata-cn-html.js',
    ];
    $aResult = $oGather->curlContentsByArr($aOption);
    if (200 != $aResult['info']['http_code']) {
        dd('页面没有下载成功');
    }
    $sResult = str_replace(['document.getElementById("tree").innerHTML=\'', '\';'], '', $aResult['results']);

    // 保存页面
    file_put_contents($sFile, $sResult);
} else
{
    $sResult = file_get_contents($sFile);
}

$oResult = HtmlDomParser::str_get_html($sResult);
if (!is_object($oResult->find('.closeChild', 0))) {
    dd('页面元素不对?? TODO Check.');
}

$aValue = [];
foreach ($oResult->find('.closeChild') as $sKey => $oVal)
{
    $aValue[] = [
        'make_id' => str_replace('pictree_', '', $oVal->id),
        'make_name' => trim($oVal->find('a', 1)->title),
        'make_nums' => str_replace([' ', '(', ')'], '', $oVal->find('a', 1)->find('span', 0)->plaintext)
    ];
}

if (!empty($aValue))
{
    $sTable = '1_make';

    // 检查表是否存在
    if (!Capsule::schema()->hasTable($sTable))
    {
        Capsule::schema()->create($sTable, function (Blueprint $table) {
            $table->increments('make_id');
            $table->string('make_name', 255)->default('');
            $table->string('make_nums', 255)->default('');
        });
    } else
    {
        Capsule::table($sTable)->truncate();
    }

    // 插入数据库
    Capsule::table($sTable)->insert($aValue);
}
echo sprintf('插入数据: %d', count($aValue));
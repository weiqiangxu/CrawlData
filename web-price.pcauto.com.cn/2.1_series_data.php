<?php
require 'config.php';

use Huluo\Extend\Gather;
use Sunra\PhpSimple\HtmlDomParser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

// 存储路径
$sPath = sprintf('%s/%s/series', APP_DOWN, $database['database']);
@mkdir($sPath, 0777, true);

// 检查表是否存在
$sTable = '2_series';
if (!Capsule::schema()->hasTable($sTable))
{
    Capsule::schema()->create($sTable, function (Blueprint $table) {
        $table->increments('series_id');
        $table->string('brand_name', 255)->default('');
        $table->string('series_name', 255)->default('');
        $table->integer('make_id')->default(0);
        $table->string('make_name', 255)->default('');
        $table->string('make_nums', 255)->default('');
    });
} else
{
    Capsule::table($sTable)->truncate();
}

$aTable = Capsule::table('1_make')->orderBy('make_id')->get();
$aTable = $aTable->transform(function($aItem) {
    return (array) $aItem;
})->toArray();
foreach ($aTable as $sKey => $aVal)
{
    $sFile = sprintf('%s/%d.html', $sPath, $aVal['make_id']);
    if (!is_file($sFile)) {
        continue;
    }

    // 解析HTML
    $sResult = file_get_contents($sFile);
    $oResult = HtmlDomParser::str_get_html($sResult);
    if (!is_object($oResult->find('li', 0))) {
        dd('下载了空页面? 正常?');
    }

    // 读取数据
    $aValue = [];
    $sBrand = '';
    foreach ($oResult->find('li') as $sKKK => $oVal)
    {
        $sItem = str_replace('product_', '', $oVal->id);
        $sText = $oVal->find('a', 0)->title;

        if ($sItem)
        {
            $aData = [
                'series_id' => $sItem,
                'brand_name' => iconv('gbk', 'utf-8', $sBrand),
                'series_name' => iconv('gbk', 'utf-8', $sText),
            ];
            $aValue[] = array_merge($aVal, $aData);
        } else
        {
            $sBrand = $sText;
        }
    }
    $oResult->clear();
    if (!empty($aValue)) {
        Capsule::table($sTable)->insert($aValue);
    }
}
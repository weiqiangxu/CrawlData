<?php
require 'config.php';

use Huluo\Extend\Gather;
use Illuminate\Database\Capsule\Manager as Capsule;

// 存储路径
$sPath = sprintf('%s/%s/series', APP_DOWN, $database['database']);
@mkdir($sPath, 0777, true);

$aTable = Capsule::table('1_make')->get();
$aTable = $aTable->transform(function($aItem) {
    return (array) $aItem;
})->toArray();
foreach ($aTable as $sKey => $aVal)
{
    $sFile = sprintf('%s/%d.html', $sPath, $aVal['make_id']);
    if (!is_file($sFile))
    {
        echo $aVal['make_id'];
        $oGather = new Gather();
        $aOption = [
            CURLOPT_URL => sprintf('http://price.pcauto.com.cn/index/js/5_5/treedata-cn-%d.js?6=3', $aVal['make_id']),
        ];

        $aResult = $oGather->curlContentsByArr($aOption);
        if ((200 == $aResult['info']['http_code']) && (false !== strpos($aResult['results'], 'var brandList_')))
        {
            $sResult = str_replace([sprintf('var brandList_%d=\'', $aVal['make_id']), '\';'], '', $aResult['results']);
            file_put_contents($sFile, $sResult);
            echo "==ok";
        }
        echo "\r\n";
    }
}
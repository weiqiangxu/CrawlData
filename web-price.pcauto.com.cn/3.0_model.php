<?php
require 'config.php';

use Huluo\Extend\Gather;
use Illuminate\Database\Capsule\Manager as Capsule;

// 存储路径
$sPath = sprintf('%s/%s/model', APP_DOWN, $database['database']);
@mkdir($sPath, 0777, true);

$aTable = Capsule::table('2_series')->orderBy('series_id')->get();
$aTable = $aTable->transform(function($aItem) {
    return (array) $aItem;
})->toArray();

$sRun = 9;
$aValue = [];
$oGather = new Gather();
foreach ($aTable as $sKey => $aVal)
{
    $sFile = sprintf('%s/%d.html', $sPath, $aVal['series_id']);
    if (!is_file($sFile))
    {
        $aValue[] = [
            'url' => sprintf('http://price.pcauto.com.cn/sg%d/', $aVal['series_id']),
            'idcard' => $sFile,
        ];

        if (isset($aValue[$sRun]))
        {
            $aResult = $oGather->curlRollingByArr($aValue);
            foreach ($aResult as $sKKK => $aVVV)
            {
                if ((200 == $aVVV['info']['http_code']) && (false !== strpos($aVVV['results'], '</html>')))
                {
                    file_put_contents($sKKK, minify($aVVV['results']));
                    echo sprintf("%s==ok\r\n", $sKKK);
                }
            }
            $aValue = [];
        }
    }
}
if (isset($aValue[0]))
{
    $aResult = $oGather->curlRollingByArr($aValue);
    foreach ($aResult as $sKKK => $aVVV)
    {
        if ((200 == $aVVV['info']['http_code']) && (false !== strpos($aVVV['results'], '</html>')))
        {
            file_put_contents($sKKK, minify($aVVV['results']));
            echo sprintf("%s==ok\r\n", $sKKK);
        }
    }
    $aValue = [];
}
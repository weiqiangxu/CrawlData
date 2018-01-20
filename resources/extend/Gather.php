<?php
namespace Huluo\Extend;

/**
 * @author huluo
 * @desc 采集常用操作
 */
class Gather
{
    /**
     * 超时时间
     */
    public $_timeout = 7;

    /**
     * Get获取数据 By file_get_content
     */
    public function fileContents($sSubmitUrl, $aSubmitVar = [])
    {
        $aSubmitOpts = array(
            'http' => array(
                'method' => 'GET',
                'timeout' => $this->_timeout,
            )
        );
        if (!empty($aSubmitVar)) {
            $aSubmitOpts = array_merge_recursive($aSubmitOpts, $aSubmitVar);
        }

        $oContext = stream_context_create($aSubmitOpts);
        $sContent = file_get_contents($sSubmitUrl, false, $oContext);
        return $sContent;
    }

    /**
     * 单线程获取数据 By Curl
     */
    public function curlContentsByArr($aOption = [])
    {
        $aDefault = [
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => $this->_timeout,
            CURLOPT_NOSIGNAL => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ];

        // 合并属性
        if (!empty($aOption))
        {
            foreach ($aOption as $sKey => $sVal)
            {
                $aDefault[$sKey] = $sVal;
            }
        }

        // 执行
        $oCh = curl_init();
        foreach ($aDefault as $sKey => $sVal) {
            curl_setopt($oCh, $sKey, $sVal);
        }
        $sHtml = curl_exec($oCh);
        $aInfo = curl_getinfo($oCh);
        $aError = curl_error($oCh);
        curl_close($oCh);
        return ['results' => $sHtml, 'info' => $aInfo, 'error' => $aError];
    }

    /**
     * 多进程获取数据 By Curl
     */
    public function curlRollingByArr($aValue = [])
    {
        $aMap = [];
        $aResult = [];
        $aDefault = [
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => $this->_timeout,
            CURLOPT_NOSIGNAL => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ];

        // 队列
        $aQueue = curl_multi_init();
        foreach ($aValue as $sKey => $aVal)
        {
            // 执行
            $oCh = curl_init();
            curl_setopt($oCh, CURLOPT_URL, $aVal['url']);

            // 合并属性
            $aOption = $aDefault;
            if (!empty($aVal['option']))
            {
                foreach ($aVal['option'] as $sCurlOpt => $mCurlVal) {
                    $aOption[$sCurlOpt] = $mCurlVal;
                }
            }

            // 设置参数
            foreach ($aOption as $sCurlOpt => $mCurlVal) {
                curl_setopt($oCh, $sCurlOpt, $mCurlVal);
            }
            curl_multi_add_handle($aQueue, $oCh);
            $aMap[(string) $oCh] = $aVal['idcard'];
        }

        // 执行
        do
        {
            while (($sCode = curl_multi_exec($aQueue, $sActive)) == CURLM_CALL_MULTI_PERFORM) ;
            if ($sCode != CURLM_OK) {
                break;
            }

            // a request was just completed -- find out which one
            while ($oDone = curl_multi_info_read($aQueue))
            {
                // get the info and content returned on the request
                $info = curl_getinfo($oDone['handle']);
                $error = curl_error($oDone['handle']);
                $results = curl_multi_getcontent($oDone['handle']);
                $aResult[$aMap[(string) $oDone['handle']]] = compact('info', 'error', 'results');

                // remove the curl handle that just completed
                curl_multi_remove_handle($aQueue, $oDone['handle']);
                curl_close($oDone['handle']);
            }

            if ($sActive > 0) {
                curl_multi_select($aQueue, 0.1);
            }

        } while ($sActive);

        curl_multi_close($aQueue);
        return $aResult;
    }
}
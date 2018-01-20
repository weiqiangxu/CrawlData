<?php

if (!function_exists('minify'))
{
    /**
     * @author huluo
     * @desc 压缩html,清除换行符,清除制表符,去掉注释标记
     */
    function minify($html)
    {
        // 清除换行符制表符
        $html = str_replace(["\r", "\n", "\t"], '', $html);
        $pattern = array("/> *([^ ]*) *</", "/[\s]+/","/<!--[^!]*-->/","/\" /","/ \"/","'/\*[^*]*\*/'");
        $replace = array( ">\\1<", " ", "", "\"", "\"", "");
        return preg_replace($pattern, $replace, $html);
    }
}

if (!function_exists('parseUrlParam'))
{
    /**
     * @author huluo
     * @desc 解析URL参数
     */
    function parseUrlParam($query)
    {
        $queryArr = explode('&', $query);
        $params = array();
        if ($queryArr['0'] !== '')
        {
            foreach ($queryArr as $param)
            {
                list($name, $value) = explode('=', $param);
                $params[urldecode($name)] = urldecode($value);
            }
        }
        return $params;
    }
}

if (!function_exists('setUrlParams'))
{
    /**
     * @author huluo
     * @desc 设置URL参数数组
     */
    function setUrlParams($cparams, $url = '')
    {
        $parse_url = $url === '' ? parse_url($_SERVER["REQUEST_URI"]) : parse_url($url);
        $query = isset($parse_url['query']) ? $parse_url['query'] : '';
        $params = parseUrlParam($query);
        foreach ($cparams as $key => $value)
        {
            $params[$key] = $value;
        }
        return $parse_url['path'].'?'.http_build_query($params);
    }
}
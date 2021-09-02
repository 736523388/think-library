<?php

use think\Lang;

// 加载对应的语言包
Lang::load(__DIR__ . '/lang/zh-cn.php', 'zh-cn');
Lang::load(__DIR__ . '/lang/en-us.php', 'en-us');

if (!function_exists('p')) {
    /**
     * 打印输出数据到文件
     * @param mixed $data 输出的数据
     * @param boolean $force 强制替换
     * @param string|null $file 文件名称
     */
    function p($data, $force = false, $file = null)
    {
        if (is_null($file)) $file = RUNTIME_PATH . date('Ymd') . '.txt';
        $str = (is_string($data) ? $data : ((is_array($data) || is_object($data)) ? print_r($data, true) : var_export($data, true))) . PHP_EOL;
        $force ? file_put_contents($file, $str) : file_put_contents($file, $str, FILE_APPEND);
    }
}

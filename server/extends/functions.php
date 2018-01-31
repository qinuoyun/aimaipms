<?php

/**
 * This7 Frame
 * @Author: else
 * @Date:   2018-01-31 13:17:29
 * @Last Modified by:   else
 * @Last Modified time: 2018-01-31 13:20:21
 */
/**
 * JSON格式输出
 * @param integer $num  错误编号
 * @param string  $tips 提示信息
 * @param array   $data 调用数据
 */
function J($num = 00000, $tips = '', $array = array()) {
    $data = array();
    if (empty($array)) {
        $data = array(
            "error" => $num,
            "msg"   => $tips,
        );
    } else {
        $data = array(
            "error" => $num,
            "msg"   => $tips,
            "data"  => $array,
        );
    }
    echo tojson($data);
    exit();
}

/**
 * 连接数据库
 * @param string $table [description]
 */
function D($table = '') {
    return sql::table($table);
}

/**
 * 数组转JSON
 * @param  array  $array 数组数据
 * @return json          返回JSON数据
 */
function tojson($array = array()) {
    return json_encode($array, JSON_UNESCAPED_UNICODE);
}

/**
 * JSON转数组
 * @param  string $json JSON数据
 * @return array        返回数组数据
 */
function toarray($json = '') {
    return json_decode($json, true);
}

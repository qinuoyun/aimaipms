<?php
/**
 * @Author: Administrator
 * @Date:   2016-12-21 13:17:28
 * @Last Modified by:   qinuoyun
 * @Last Modified time: 2018-01-31 14:27:39
 */
use server\models\demo;

class demo {

    public function __construct() {
        # code...
    }

    public function test() {
        $sql = C("sql");
        P($sql);
    }
}
<?php
/**
 * @Author: Else
 * @Date:   2017-05-02 10:10:20
 * @Last Modified by:   Else
 * @Last Modified time: 2017-07-05 10:09:01
 */
/**
 * 错误码10000
 */
class login {

    public function __construct() {
        # code...
    }

    public function login() {
        $type = _POST['type'];
        switch ($type) {
        case '2':
            $db    = D("user");
            $where = array(
                "username" => _POST['username'],
                "password" => md5(md5(md5(_POST['password']))),
            );
            $row = $db->where($where)->first();
            if ($row) {
                $row['login_key'] = C("system", "token");
                J(0, "登录成功", $row);
            } else {
                J(10001, "登录失败");
            }
            break;
        case '1':

            break;

        default:

            break;
        }
    }
    /**
     * 注册
     */
    public function register() {
        (empty($data['username']) || empty($data['password']) || empty($data['tel'])) && J(10002, "用户名、密码、手机不能为空");
        $where = array("tel" => $data['tel']);
        $db    = D("user");
        $row   = $db->where($where)->first();
        if (empty($row)) {
            $db->insert($data);
            J(10003, "注册成功");
        } else {
            J(10004, "该手机号已注册");
        }
    }
}
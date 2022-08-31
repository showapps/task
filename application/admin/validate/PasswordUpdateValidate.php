<?php

namespace app\admin\validate;


use think\Validate;

class PasswordUpdateValidate extends Validate
{

    protected $rule = [
        'password' => ['require'],
        'npassword' => ['require', 'length:6,22'],
        'rpassword' => ['require', 'confirm:npassword']
    ];

    protected $message = [
        'password.require' => '密码必填',
        'npassword.require' => '新登录密码必填',
        'npassword.length' => '新登录密码长度 6 ~ 22 位',
        'rpassword.require' => '确认密码必填',
        'rpassword.length' => '新登录密码 与 确认密码长度不一致',
        'rpassword.confirm' => '两次登录的密码不一致',
    ];


    protected $scene = [
        'password' => ['password','npassword','rpassword'],
    ];

}
<?php


namespace app\admin\validate;


use think\facade\Session;
use think\Validate;

class LoginValidate extends Validate
{

    protected $rule =   [
        'account'  => ['require'],
        'password'  => ['require'],
        'captcha'  => ['require','checkCaptcha'],
    ];

    protected $message  =   [
        'account.require' => '账户名必填',
        'password.require' => '密码必填',
        'captcha.require' => '验证码必填',
    ];


    protected $scene = [
        'account'  =>  ['account','password','captcha'],
    ];



    // 检查图片验证码
    protected function checkCaptcha($value,$rule,$data=[])
    {

        $code = Session::get('admin_captcha_image');
        if($value != $code){
            return '验证码错误';
        }

        return true;

    }


}
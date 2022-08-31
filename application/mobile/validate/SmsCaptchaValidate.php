<?php


namespace app\mobile\validate;


use think\Validate;

class SmsCaptchaValidate extends Validate
{

    protected $rule =   [
        'phone'  => ['require','mobile'],
    ];

    protected $message  =   [
        'phone.require' => '手机号码不可为空',
        'phone.mobile' => '手机号码格式错误',
    ];


    protected $scene = [
        'phone'  =>  ['phone'],
    ];


}
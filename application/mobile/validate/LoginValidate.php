<?php


namespace app\mobile\validate;


use think\Validate;

class LoginValidate extends Validate
{

    protected $rule =   [
        'phone'  => ['require'],
        'captcha_sms'  => ['require','checkCaptchaSms'],
    ];

    protected $message  =   [
        'phone.require' => '手机号码不可为空',
        'captcha_sms.require' => '验证码必填',
    ];


    protected $scene = [
        'phone'  =>  ['phone','captcha_sms'],
    ];



    // 检查图片验证码
    protected function checkCaptchaSms($value,$rule,$data=[])
    {

        $captcha_sms = $data['captcha_sms'];
        if((!$captcha_sms) || (!is_array($captcha_sms))){
            return true;
        }

        if((!isset($captcha_sms['id'])) || (!isset($captcha_sms['code']))){
            return '验证码必填';
        }

        if(!check_sms_code($captcha_sms['id'],$data['phone'],$captcha_sms['code'])){
            return '验证码错误';
        }

        return true;
    }


}
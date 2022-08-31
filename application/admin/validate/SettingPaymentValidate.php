<?php

namespace app\admin\validate;


use think\Validate;

class SettingPaymentValidate extends Validate
{

    protected $rule = [
        'wechat_payment_mp_app_id' => ['require'],
        'wechat_payment_mch_id' => ['require'],
        'wechat_payment_key' => ['require'],
    ];

    protected $message = [
        'wechat_payment_mp_app_id.require' => '公众号 appid 必填',
        'wechat_payment_mch_id.require' => '商户ID必填',
        'wechat_payment_key.require' => '商户秘钥必填',
    ];

}
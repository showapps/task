<?php


namespace app\mobile\validate;


use think\Validate;

class DepositRechargeValidate extends Validate
{

    protected $rule =   [
        'money'  => ['require','float','egt:10'],
    ];

    protected $message  =   [
        'money.require' => '充值金额必填',
        'money.float' => '充值金额必填是数字',
        'money.egt' => '充值金额不能小于10元',
    ];

}
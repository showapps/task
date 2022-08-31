<?php

namespace app\admin\validate;


use think\Validate;

class SettingWithdrawValidate extends Validate
{

    protected $rule = [
        'withdraw_min_money' => ['require','egt:0.01'],
        'withdraw_max_money' => ['require','checkMaxMoney'],
    ];

    protected $message = [
        'withdraw_min_money.require' => '请填写提现最小金额',
        'withdraw_min_money.egt' => '提现最小金额不能小于0.01',
        'withdraw_max_money.require' => '请填写提现最大金额',
    ];



    // 检查大区代理开通金额
    protected function checkMaxMoney($value,$rule,$data=[])
    {
        if(abs(floatval($value)) < abs(floatval($data['withdraw_min_money']))){
            return '提现最大金额不能小于最小金额';
        }
        return true;
    }
}
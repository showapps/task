<?php

namespace app\admin\validate;


use think\Validate;

class SettingMoneyValidate extends Validate
{

    protected $rule = [
        'deposit_min_money' => ['require', 'between:1,999999'],
        'deposit_recharge_min_money' => ['require', 'between:1,999999'],
        'deposit_recharge_max_money' => ['require', 'between:1,999999'],
        'recharge_min_money' => ['require', 'between:1,999999'],
    ];

    protected $message = [
        'deposit_min_money.require' => '请填写最小押金金额',
        'deposit_min_money.between' => '最小押金金额 1 ~ 999999 之间',
        'deposit_recharge_min_money.require' => '请填写押金最小充值金额',
        'deposit_recharge_min_money.between' => '押金最小充值金额 1 ~ 999999 之间',
        'deposit_recharge_max_money.require' => '请填写押金最大充值金额',
        'deposit_recharge_max_money.between' => '押金最大充值金额 1 ~ 999999 之间',
        'recharge_min_money.require' => '请填写钱包最小充值金额',
        'recharge_min_money.between' => '钱包最小充值金额 1 ~ 999999 之间',
        'recharge_max_money.require' => '请填写钱包最大充值金额',
        'recharge_max_money.between' => '钱包最大充值金额 1 ~ 999999 之间',
    ];
}
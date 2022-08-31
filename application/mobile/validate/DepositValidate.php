<?php


namespace app\mobile\validate;


use think\Validate;

class DepositValidate extends Validate
{

    protected $rule =   [
        'id'  => ['require','number'],
        'money'  => ['require','number'],
    ];

    protected $message  =   [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'money.require' => '提现金额必填',
        'money.number' => '提现金额必须是整数',
    ];


    protected $scene = [
        'detail'  =>  ['id'],
        'withdraw'  =>  ['money'],
    ];

}
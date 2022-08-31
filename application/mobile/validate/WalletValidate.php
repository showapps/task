<?php


namespace app\mobile\validate;


use think\Validate;

class WalletValidate extends Validate
{

    protected $rule =   [
        'id'  => ['require','number'],
        'account_type'  => ['require','in:1,2'],
        'money'  => ['require'],
    ];

    protected $message  =   [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'account_type.require' => '到账方式必须选择',
        'money.require' => '提现金额必填',
    ];


    protected $scene = [
        'detail'  =>  ['id'],
        'withdraw'  =>  ['account_type','money'],
    ];

}
<?php


namespace app\mobile\validate;


use think\Validate;

class WalletPaymentValidate extends Validate
{

    protected $rule =   [
        'order_id'  => ['require','number'],
    ];

    protected $message  =   [
        'order_id.require' => '请选择支付订单',
        'order_id.number' => '支付订单必须是整数',
    ];

}
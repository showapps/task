<?php

namespace app\mobile\validate;

use think\Validate;

class OrdersValidate extends Validate
{
    protected $rule =   [
        'id'  => 'require',
    ];

    protected $message  =   [
        'id.require' => '请选择订单',
    ];


    protected $scene = [
        'detail'  =>  ['id'],
        'cancel'  =>  ['id'],
        'delete'  =>  ['id'],
    ];
}
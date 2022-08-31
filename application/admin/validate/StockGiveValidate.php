<?php


namespace app\admin\validate;


use think\Validate;

class StockGiveValidate extends Validate
{

    protected $rule = [
        'user_id' => ['require', 'number'],
        'stock_type' => ['require', 'in:1,2'],
        'number' => ['require', 'number', 'egt:1'],
    ];

    protected $message = [
        'id.require' => '请选择用户',
        'id.number' => '选择用户无效',
        'stock_type.require' => '股权类型必须选择',
        'stock_type.in' => '无效的股权类型值',
        'number.require' => '请输入赠予的股数',
        'number.number' => '赠予的股数无效',
        'number.egt' => '赠予的股数不能小于 1 股',
    ];


    protected $scene = [
        'stock_give_view' => ['user_id'],
        'stock_give' => ['user_id', 'stock_type', 'number'],
    ];

}
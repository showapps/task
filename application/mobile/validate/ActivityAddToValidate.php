<?php


namespace app\mobile\validate;


use think\Validate;

class ActivityAddToValidate extends Validate
{

    protected $rule =   [
        'id'  => ['require','number'],
        'number'  => ['require','number','egt:1'],
    ];

    protected $message  =   [
        'id.require' => '请选择活动',
        'id.number' => '活动值无效',
        'number.require' => '追加数量必填',
        'number.number' => '追加数量无效',
        'number.egt' => '追加数量不能小于1个',
    ];

}
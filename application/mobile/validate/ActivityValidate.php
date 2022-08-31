<?php


namespace app\mobile\validate;


use think\Validate;

class ActivityValidate extends Validate
{

    protected $rule =   [
        'id'  => ['require','number'],
        'hour'  => ['require','number','between:1,168']
    ];

    protected $message  =   [
        'id.require' => '请选择活动',
        'id.number' => '选择活动无效',
        'hour.require' => '推荐小时必填',
        'hour.number' => '推荐小时必须是数字',
        'hour.between' => '推荐小时1~168小时之间',
    ];


    protected $scene = [
        'detail'  =>  ['id'],
        'refresh'  =>  ['id'],
        'recommend'  =>  ['id','hour'],
    ];

}
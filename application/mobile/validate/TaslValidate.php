<?php


namespace app\mobile\validate;


use think\Validate;

class TaslValidate extends Validate
{

    protected $rule =   [
        'id'  => ['require','number'],
    ];

    protected $message  =   [
        'id.require' => '请选择任务',
        'id.number' => '选择任务值无效',
    ];


    protected $scene = [
        'detail'  =>  ['id'],
        'cancel'  =>  ['id'],
        'delete'  =>  ['id'],
    ];

}
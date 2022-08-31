<?php


namespace app\mobile\validate;


use think\Validate;

class HelpValidate extends Validate
{

    protected $rule =   [
        'id'  => ['require','number']
    ];

    protected $message  =   [
        'id.require' => '请选择帮助',
        'id.number' => '选择帮助无效'
    ];


    protected $scene = [
        'detail'  =>  ['id'],
    ];

}
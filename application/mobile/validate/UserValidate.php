<?php


namespace app\mobile\validate;


use think\Validate;

class UserValidate extends Validate
{

    protected $rule =   [
        'id'  => ['require','number'],
    ];

    protected $message  =   [
        'id.require' => '请选择用户',
        'id.number' => '用户编号无效',
    ];


    protected $scene = [
        'detail'  =>  ['id'],
    ];

}
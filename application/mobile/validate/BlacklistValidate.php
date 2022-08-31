<?php


namespace app\mobile\validate;


use think\Validate;

class BlacklistValidate extends Validate
{

    protected $rule =   [
        'id'  => ['require','number'],
    ];

    protected $message  =   [
        'id.require' => '请选择黑名单',
        'id.number' => '黑名单无效',
    ];


    protected $scene = [
        'detail'  =>  ['id'],
    ];

}
<?php

namespace app\admin\validate;


use think\Validate;

class UserBankValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
    ];


    protected $scene = [
        'detail' => ['id'],
    ];

}
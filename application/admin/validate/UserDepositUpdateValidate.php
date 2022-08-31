<?php

namespace app\admin\validate;


use think\Validate;

class UserDepositUpdateValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'set_type' => ['require', 'in:1,2'],
        'money' => ['require', 'egt:1', 'elt:9999'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'set_type.require' => '请选择变动类型',
        'set_type.in' => '变动类型无效',
        'money.require' => '请输入变动金额',
        'money.egt' => '变动金额不能小于1元',
        'money.elt' => '变动金额不能大于9999元',
    ];

}

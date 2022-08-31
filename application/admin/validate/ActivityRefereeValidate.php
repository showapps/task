<?php

namespace app\admin\validate;


use think\Validate;

class ActivityRefereeValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'type' => ['require','in:1,2'],
        'reasons' => ['require','length:1,50'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'type.require' => '处理类型必须选择',
        'type.in' => '无效的处理类型值',
        'reasons.require' => '备注信息必填',
        'reasons.length' => '备注信息1~50位之间',
    ];


}
<?php

namespace app\admin\validate;


use think\Validate;

class TaskValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'status' => ['require','in:3,100'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'status.require' => '状态必须选择',
        'status.in' => '无效的状态值',
    ];


    protected $scene = [
        'detail' => ['id'],
        'referee_view' => ['id'],
        'referee' => ['id', 'status'],
        'delete' => ['id'],
    ];

}
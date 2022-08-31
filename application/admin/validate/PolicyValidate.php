<?php

namespace app\admin\validate;


use think\Validate;

class PolicyValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'title' => ['require','length:1,80'],
        'read' => ['number'],
        'give' => ['number'],
        'status' => ['require','in:1,2'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'title.require' => '标题必填',
        'title.length' => '标题长度最大80位',
        'read.number' => '阅读次数必须是数字',
        'give.number' => '点赞次数必须是数字',
        'status.require' => '状态必须选择',
        'status.in' => '无效的状态值',
    ];


    protected $scene = [
        'detail' => ['id'],
        'create' => ['title', 'read', 'give', 'status'],
        'updateView' => ['id'],
        'update' => ['id', 'title', 'read', 'give', 'status'],
        'delete' => ['id'],
    ];

}
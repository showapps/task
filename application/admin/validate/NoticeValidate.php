<?php

namespace app\admin\validate;


use think\Validate;

class NoticeValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'content' => ['require','length:1,80'],
        'event' => ['require','in:1,2,3'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'content.require' => '内容必填',
        'content.length' => '内容长度最大80位',
        'event.require' => '类型必须选择',
        'event.in' => '无效的类型值',
    ];


    protected $scene = [
        'detail' => ['id'],
        'create' => ['event', 'content'],
        'updateView' => ['id'],
        'update' => ['id', 'event', 'content'],
        'delete' => ['id'],
    ];

}
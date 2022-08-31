<?php

namespace app\admin\validate;


use think\Validate;

class HelpValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'title' => ['require','length:1,80'],
        'category_id' => ['require','number'],
        'status' => ['require','in:1,2'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'title.require' => '标题必填',
        'title.length' => '标题长度最大80位',
        'category_id.require' => '请选择分类',
        'category_id.number' => '选择的分类无效',
        'status.require' => '状态必须选择',
        'status.in' => '无效的状态值',
    ];


    protected $scene = [
        'detail' => ['id'],
        'create' => ['title', 'category_id', 'status'],
        'updateView' => ['id'],
        'update' => ['id', 'title', 'category_id', 'status'],
        'delete' => ['id'],
    ];

}
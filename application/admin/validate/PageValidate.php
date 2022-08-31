<?php

namespace app\admin\validate;


use think\Validate;

class PageValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'title' => ['require','length:1,80'],
        'name' => ['require','alphaDash','length:1,64'],
        'status' => ['require','in:1,2'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'title.require' => '标题必填',
        'title.length' => '标题长度最大80位',
        'name.require' => '名称必填',
        'name.alphaDash' => '名称只支持 字母、数字、下划线 组合',
        'name.length' => '名称长度最大64位',
        'status.require' => '状态必须选择',
        'status.in' => '无效的状态值',
    ];


    protected $scene = [
        'detail' => ['id'],
        'create' => ['title', 'name', 'status'],
        'updateView' => ['id'],
        'update' => ['id', 'title', 'status'],
        'delete' => ['id'],
    ];

}
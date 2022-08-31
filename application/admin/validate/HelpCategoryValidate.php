<?php

namespace app\admin\validate;


use think\Validate;

class HelpCategoryValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'title' => ['require','length:1,10'],
        'status' => ['require','in:1,2'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'title.require' => '名称必填',
        'title.length' => '名称长度最大10位',
        'status.require' => '状态必须选择',
        'status.length' => '状态值无效',
    ];


    protected $scene = [
        'detail' => ['id'],
        'create' => ['title', 'status'],
        'updateView' => ['id'],
        'update' => ['id', 'title', 'status'],
        'delete' => ['id'],
    ];

}
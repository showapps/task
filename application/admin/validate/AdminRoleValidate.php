<?php

namespace app\admin\validate;


use think\Validate;

class AdminRoleValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'title' => ['require','length:1,10'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'title.require' => '名称必填',
        'title.length' => '名称长度最大10位',
    ];


    protected $scene = [
        'detail' => ['id'],
        'create' => ['title'],
        'updateView' => ['id'],
        'update' => ['id', 'title'],
        'access_view'  =>  ['role_id'],
        'access'  =>  ['role_id'],
        'delete' => ['id'],
    ];

}
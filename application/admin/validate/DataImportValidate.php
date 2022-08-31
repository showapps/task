<?php


namespace app\admin\validate;


use think\Validate;

class DataImportValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'ids' => ['require', 'array'],
        'type' => ['require','in:1'],
        'describe' => ['require','length:1,100'],
        'excel' => ['require'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'ids.require' => '请选择数据',
        'ids.array' => '请选择数据',
        'type.require' => '类型必须选择',
        'type.in' => '无效的类型值',
        'describe.require' => '请填写描述',
        'describe.length' => '描述长度 1~100位之间',
        'excel.require' => '请上传Excel文件',
    ];


    protected $scene = [
        'detail' => ['id'],
        'create' => ['type','describe','excel'],
        'audit' => ['id','status'],
        'cancel' => ['id'],
        'delete' => ['id'],
    ];

}
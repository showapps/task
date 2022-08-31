<?php

namespace app\admin\validate;


use think\Validate;

class ActivityCategoryValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'title' => ['require','length:1,10'],
        'icon' => ['require','url'],
        'describe' => ['require','length:1,20'],
        'min_price' => ['require','float','egt:0.01'],
        'min_number' => ['require','number','egt:1'],
        'status' => ['require','in:1,2'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'title.require' => '名称必填',
        'title.length' => '名称长度1~10位之间',
        'icon.require' => '图标必须上传',
        'icon.url' => '图标不是一个有效的地址',
        'describe.require' => '描述必填',
        'describe.length' => '描述长度1~20位之间',
        'min_price.require' => '最小单价必填',
        'min_price.number' => '最小单价必须是数字',
        'min_price.egt' => '最小单价不能小于0.01',
        'min_number.require' => '最少单价必填',
        'min_number.float' => '最少数量必须是数字',
        'min_number.egt' => '最少数量不能小于1',
        'status.require' => '状态必须选择',
        'status.length' => '状态值无效',
    ];


    protected $scene = [
        'detail' => ['id'],
        'create' => ['title', 'describe', 'min_price', 'min_number', 'status'],
        'updateView' => ['id'],
        'update' => ['id', 'title', 'describe', 'min_price', 'min_number', 'status'],
        'delete' => ['id'],
    ];

}
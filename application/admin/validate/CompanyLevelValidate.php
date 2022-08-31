<?php

namespace app\admin\validate;


use think\Validate;

class CompanyLevelValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'title' => ['require','length:1,80'],
        'sale_prices' => ['require','array','length:3'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'title.require' => '标题必填',
        'title.length' => '标题长度最大80位',
        'sale_prices.require' => '价格必填',
        'sale_prices.array' => '价格数据格式错误',
        'sale_prices.length' => '价格长度有误',
    ];


    protected $scene = [
        'detail' => ['id'],
        'updateView' => ['id'],
        'update' => ['id', 'title', 'sale_prices'],

    ];

}
<?php


namespace app\mobile\validate;

use think\Validate;

class MeBuyRefreshValidate extends Validate
{

    protected $rule =   [
        'sku_id'  => ['require','number'],
    ];

    protected $message  =   [
        'sku_id.require' => '请选择开通规格',
        'sku_id.number' => '请选择开通规格',
    ];

}
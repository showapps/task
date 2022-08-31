<?php


namespace app\mobile\validate;


use think\Validate;

class PageValidate extends Validate
{

    protected $rule =   [
        'name'  => ['require','alphaDash'],
    ];

    protected $message  =   [
        'name.require' => '请选择单页',
        'name.alphaDash' => '单页名称无效',
    ];


    protected $scene = [
        'detail'  =>  ['name'],
    ];

}
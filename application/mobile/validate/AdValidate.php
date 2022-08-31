<?php


namespace app\mobile\validate;


use think\Validate;

class AdValidate extends Validate
{

    protected $rule =   [
        'position'  => ['require','alphaDash'],
    ];

    protected $message  =   [
        'position.require' => '请选择广告位',
        'position.alphaDash' => '广告位标识无效',
    ];


    protected $scene = [
        'detail'  =>  ['position'],
    ];

}
<?php

namespace app\mobile\validate;


use think\Validate;

class TaskReportValidate extends Validate
{

    protected $rule =   [
        'id'  => ['require','number'],
        'identity'  => ['require','in:1,2'],
        'text'  => ['require','length:1,120'],
        'images'  => ['array','length:0,6'],
    ];

    protected $message  =   [
        'id.require' => '请选择任务',
        'id.number' => '任务值无效',
        'identity.require' => '请选择身份',
        'identity.number' => '身份值无效',
        'text.require' => '请输入文本描述',
        'text.length' => '文本描述1~50字',
        'images.array' => '举例图片数据无效',
        'images.length' => '举例图片最多不超过6张',
    ];


    protected $scene = [
        'report_create'  =>  ['id','identity','text','images'],
        'report_argue'  =>  ['id','identity','text','images'],
    ];


}
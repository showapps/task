<?php

namespace app\mobile\validate;


use think\Validate;

class TaskAuditValidate extends Validate
{

    protected $rule =   [
        'id'  => ['require','number'],
        'ids'  => ['require','array','length:1,10'],
        'text'  => ['require','length:1,120'],
        'images'  => ['array','length:0,6'],
    ];

    protected $message  =   [
        'id.require' => '请选择任务',
        'id.number' => '任务值无效',
        'ids.require' => '请选择任务',
        'ids.array' => '任务值无效',
        'ids.length' => '单词最多操作10个任务',
        'text.require' => '请输入驳回理由',
        'text.length' => '请输入1~50字',
        'images.array' => '描述图片数据无效',
        'images.length' => '描述图片最多不超过6张',
    ];


    protected $scene = [
        'pass'  =>  ['id'],
        'pass_list'  =>  ['ids'],
        'reject'  =>  ['id','text','images'],
        'recheck_reject'  =>  ['id','text','images'],
    ];


}
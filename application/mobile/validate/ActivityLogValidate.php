<?php

namespace app\mobile\validate;


use think\Validate;

class ActivityLogValidate extends Validate
{

    protected $rule =   [
        'activity_id'  => ['require','number'],
    ];

    protected $message  =   [
        'activity_id.require' => '请选择活动',
        'activity_id.number' => '选择活动无效',
    ];


    protected $scene = [
        'auditList'  =>  ['activity_id'],
        'refreshList'  =>  ['activity_id'],
        'addtoList'  =>  ['activity_id'],
        'recommendList'  =>  ['activity_id'],
    ];

}
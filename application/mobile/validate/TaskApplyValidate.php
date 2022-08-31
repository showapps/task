<?php


namespace app\mobile\validate;


namespace app\mobile\validate;


use think\Validate;

class TaskApplyValidate extends Validate
{

    protected $rule = [
        'activity_id' => ['require', 'number'],
    ];

    protected $message = [
        'id.require' => '请选择活动',
        'id.number' => '活动值无效',
    ];

}
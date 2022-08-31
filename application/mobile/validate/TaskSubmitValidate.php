<?php


namespace app\mobile\validate;


use think\Validate;

class TaskSubmitValidate extends Validate
{

    protected $rule =   [
        'id'  => ['require','number'],
        'steps'  => ['require','array','checkStep'],
        'text_require'  => ['length:0,120'],
    ];

    protected $message  =   [
        'id.require' => '请选择活动',
        'id.number' => '活动值无效',
        'steps.require' => '请设置任务步骤',
        'steps.array' => '任务步骤无效',
        'text_require.length' => '提交数据长度不能超过100位',
    ];

    // 检查步骤
    protected function checkStep($value,$rule,$data=[])
    {

        if(count($value) < 1){
            return '请上传任务步骤';
        }

        return true;
    }

}
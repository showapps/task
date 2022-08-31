<?php

namespace app\admin\validate;


use think\Validate;

class FeedbackValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'status' => ['require', 'in:100,2'],
        'result' => ['require', 'length:1,100'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'status.require' => '请选择状态',
        'status.in' => '状态值无效',
        'result.require' => '处理信息必填',
        'result.length' => '处理信息长度150字以内',
    ];


    protected $scene = [
        'detail' => ['id'],
        'update_view' => ['id'],
        'update' => ['id','status','result'],
    ];



}
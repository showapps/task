<?php

namespace app\admin\validate;


use think\Validate;

class BlacklistValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'user_id' => ['require', 'number'],
        'content' => ['require','length:1,100'],
        'authoritys' => ['require','array'],
        'end_dt' => ['require','dateFormat:Y-m-d'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'user_id.require' => '请选择用户',
        'user_id.number' => '请选择用户',
        'content.require' => '备注信息必填',
        'content.length' => '备注信息长度150字以内',
        'authoritys.require' => '拉黑权限必须选择',
        'authoritys.array' => '拉黑权限无效的值',
        'end_dt.require' => '解封时间必须选择',
        'end_dt.dateFormat' => '解封时间无效的值',
    ];


    protected $scene = [
        'detail' => ['id'],
        'create' => ['user_id','content','authoritys','end_dt'],
        'update_view' => ['id'],
        'update' => ['id','content','authoritys','end_dt'],
        'delete' => ['id'],
    ];



}
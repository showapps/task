<?php

namespace app\mobile\validate;


use think\Validate;

class ChatValidate extends Validate
{

    protected $rule =   [
        'id'  => ['require','number'],
        'user_id'  => ['require','number'],
        'type'  => ['require','in:1,2'],
        'content'  => ['require','length:1,200'],
    ];

    protected $message  =   [
        'id.require' => '请选择聊天窗口',
        'id.number' => '聊天窗口无效',
        'user_id.require' => '请选择接收用户',
        'user_id.number' => '接收用户无效',
        'type.require' => '消息类型必选',
        'type.in' => '消息类型无效',
        'content.require' => '消息内容不能为空',
        'content.length' => '消息内容最大长度200位',
    ];


    protected $scene = [
        'detail'  =>  ['id'],
        'open'  =>  ['user_id'],
        'send'  =>  ['id','type','content'],
    ];


}
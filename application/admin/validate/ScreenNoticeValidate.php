<?php

namespace app\admin\validate;


use think\Validate;

class ScreenNoticeValidate extends Validate
{

    protected $rule = [
        'content' => ['require', 'length:1,200'],
    ];

    protected $message = [
        'content.require' => '公告内容必填',
        'content.length' => '公告内容长度 1 ~ 200 字之间',
    ];


}
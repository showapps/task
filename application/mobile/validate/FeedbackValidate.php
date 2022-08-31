<?php


namespace app\mobile\validate;


use think\Validate;

class FeedbackValidate extends Validate
{

    protected $rule =   [
        'content'  => ['require','length:1,500'],
        'images'  => ['require','array','length:1,9'],
    ];

    protected $message  =   [
        'content.require' => '请输入反馈意见',
        'content.length' => '反馈意见1~500位字符',
        'images.require' => '最少上传1张反馈图片',
        'images.array' => '最少上传1张反馈图片',
        'images.length' => '最多上传9张反馈图片',
    ];


    protected $scene = [
        'create'  =>  ['content','images'],
    ];

}
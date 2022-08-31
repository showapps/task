<?php

namespace app\admin\validate;


use think\Validate;

class SettingUploadValidate extends Validate
{

    protected $rule = [
        'oss_access_id' => ['require'],
        'oss_access_secret' => ['require'],
        'oss_endpoint' => ['require'],
        'oss_bucket' => ['require'],
        'oss_domain_header' => ['require'],
        'oss_image_max_size' => ['require'],
        'oss_image_allow_exts' => ['require'],
        'oss_video_max_size' => ['require'],
        'oss_video_allow_exts' => ['require'],
        'oss_voice_max_size' => ['require'],
        'oss_voice_allow_exts' => ['require'],
    ];

    protected $message = [
        'oss_access_id.require' => 'access_id 必填',
        'oss_access_secret.require' => 'access_secret 必填',
        'oss_endpoint.require' => 'endpoint 必填',
        'oss_bucket.require' => 'bucket 必填',
        'oss_domain_header.require' => '域名前缀 必填',
        'oss_image_max_size.require' => '图片最大 必填',
        'oss_image_allow_exts.require' => '图片支持后缀 必填',
        'oss_video_max_size.require' => '视频最大 必填',
        'oss_video_allow_exts.require' => '视频支持后缀 必填',
        'oss_voice_max_size.require' => '音频最大 必填',
        'oss_voice_allow_exts.require' => '音频支持后缀 必填',
    ];

}
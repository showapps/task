<?php

namespace app\admin\validate;


use think\Validate;

class SettingBasicValidate extends Validate
{

    protected $rule = [
        'site_name' => ['require', 'length:1,10'],
        'site_domain' => ['require'],
        'site_logo' => ['require', 'url'],
        'site_mobile_logo' => ['require', 'url'],
        'db_hostname' => ['require'],
        'db_hostport' => ['require'],
        'db_database' => ['require'],
        'db_username' => ['require'],
        'db_password' => ['require'],
        'cache_host' => ['require'],
        'cache_port' => ['require'],
        'mobile_mp_link' => ['require','url'],
        'mobile_mp_app_id' => ['require'],
        'mobile_mp_app_secret' => ['require'],
    ];

    protected $message = [
        'site_name.require' => '应用名称必填',
        'site_name.length' => '应用名称长度1~10位之间',
        'site_domain.require' => '应用域名必填',
        'site_logo.require' => '网站图标必须上传',
        'site_logo.url' => '网站图标必须是可访问的图片地址',
        'site_mobile_logo.require' => '手机图标必须上传',
        'site_mobile_logo.url' => '手机图标必须是可访问的图片地址',
        'db_hostname.require' => '数据库主机地址必填',
        'db_hostport.require' => '数据库访问端口必填',
        'db_database.require' => '数据库数据库名必填',
        'db_username.require' => '数据库登录账号必填',
        'db_password.require' => '数据库登录密码必填',
        'cache_host.require' => '缓存主机地址必填',
        'cache_port.require' => '缓存访问端口必填',
        'mobile_mp_link.require' => '公众号网址必填',
        'mobile_mp_link.url' => '公众号网址必须是合格的Url地址',
        'mobile_mp_app_id.require' => '公众号App id必填',
        'mobile_mp_app_secret.require' => '公众号App secret必填',
    ];
    
}
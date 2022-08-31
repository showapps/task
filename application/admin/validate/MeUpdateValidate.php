<?php
/**
 * MeUpdateValidate.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/8
 */

namespace app\admin\validate;


use think\Validate;

class MeUpdateValidate extends Validate
{

    protected $rule =   [
        'user_name'  => ['require','alphaDash','length:5,20'],
        'nick_name'  => ['require','length:1,20'],
        'true_name'  => ['require','length:1,20'],
    ];

    protected $message  =   [
        'user_name.require' => '登录名必填',
        'user_name.alphaDash' => '登录名只支持 字母、数字、下划线 组合',
        'user_name.length' => '新登录名长度 5 ~ 20 位',
        'nick_name.require' => '昵称必填',
        'nick_name.length' => '新昵称长度 1 ~ 20 位',
        'true_name.require' => '姓名必填',
        'true_name.length' => '新姓名长度 1 ~ 20 位',
    ];


    protected $scene = [
        'setting'  =>  ['user_name','nick_name','true_name'],
    ];




}
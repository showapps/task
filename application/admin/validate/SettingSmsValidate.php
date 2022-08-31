<?php
/**
 * SettingSmsValidate.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/11/16
 */

namespace app\admin\validate;


use think\Validate;

class SettingSmsValidate extends Validate
{

    protected $rule = [
        'sms_access_id' => ['require'],
        'sms_access_secret' => ['require'],
        'sms_sign_name' => ['require'],
    ];

    protected $message = [
        'sms_access_id.require' => 'access_id必填',
        'sms_access_secret.require' => 'access_secret必填',
        'sms_sign_name.require' => '签名名称必填',
    ];

}
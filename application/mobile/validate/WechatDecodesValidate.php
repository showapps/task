<?php
/**
 * WechatDecodesValidate.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2019-11-08
 */

namespace app\mobile\validate;

use think\Validate;

class WechatDecodesValidate extends Validate
{
    protected $rule =   [
        'encryptedData'  => 'require',
        'iv'  => 'require',
    ];

    protected $message  =   [
        'encryptedData.require' => 'encryptedData 不能为空',
        'iv.require' => 'iv 不能为空',
    ];
}
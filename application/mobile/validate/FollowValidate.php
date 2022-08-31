<?php
/**
 * FollowValidate.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/10/1
 */

namespace app\mobile\validate;


use think\Validate;

class FollowValidate extends Validate
{

    protected $rule =   [
        'followed_user_id'  => ['require','number']
    ];

    protected $message  =   [
        'followed_user_id.require' => '请选择用户',
        'followed_user_id.number' => '选择用户无效'
    ];


    protected $scene = [
        'follow'  =>  ['followed_user_id'],
        'unFollow'  =>  ['followed_user_id'],
    ];

}
<?php
/**
 * AuthTrait.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/1
 */

namespace app\mobile\traits;


use think\facade\Request;
use Tools\Auth;

trait AuthTrait
{

    use ModuleTrait;

    static public $user_id = 0;
    static public $user = [];
    static public $token = '';



    /**
     * 授权用户信息
     *
     * @return array
     * */
    public function initAuthInfo($token = '')
    {

        if(!$token){
            $token = Request::param('token','');
        }
        self::$user = Auth::guard(self::$module_name)
            ->token($token)
            ->user();

        if(self::$user){
            self::$user_id = self::$user['id'];
            self::$token = $token;
        }

    }

}
<?php

namespace app\admin\traits;


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
    public function initAuthInfo()
    {

        $token = Request::param('token','');
        self::$user = Auth::guard(self::$module_name)
            ->token($token)
            ->user();

        if(self::$user){
            self::$user_id = self::$user['id'];
            self::$token = $token;
        }

    }

}
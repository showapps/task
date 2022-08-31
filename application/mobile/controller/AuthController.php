<?php


namespace app\mobile\controller;


use app\mobile\traits\ModuleTrait;
use think\facade\Request;
use Tools\Auth;
use Tools\Responses;

class AuthController
{

    use ModuleTrait;


    /**
     * 退出登录
     *
     * @return Responses
     * */
    public function logout()
    {

        $token = Request::param('token','');
        Auth::guard(self::$module_name)->token($token)->destroy();

        return Responses::data(200, 'success');

    }



}
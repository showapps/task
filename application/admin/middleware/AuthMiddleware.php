<?php
declare (strict_types = 1);

namespace app\admin\middleware;

use app\common\exception\RequestException;
use app\admin\traits\ModuleTrait;
use app\common\model\AdminModel;
use think\facade\Session;
use Tools\Auth;
use Tools\Responses;

class AuthMiddleware
{

    use ModuleTrait;


    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Responses
     */
    public function handle($request, \Closure $next)
    {

        $token = Session::get('admin_auth_token');
        if(!$token){
            throw new RequestException('登录过期，请重新登录！',40101);
        }

        if(!Auth::guard(self::$module_name)->token($token)->check()){
            throw new RequestException('登录过期，请重新登录！',40101);
        }

        $user = Auth::guard(self::$module_name)->token($token)->user();
        if(!Session::get('admin_auth_info')){
            $admin_auth_info = Session::get('admin_auth_info');
            if((!isset($admin_auth_info['token'])) || $admin_auth_info['token'] != $token){
                $admin = AdminModel::where('user_id','=',$user['id'])->find();
                Session::set('admin_auth_info',['token'=>$token]);
                Session::set('role_id',$admin['role_id']);
            }
        }


        return $next($request);

    }
}
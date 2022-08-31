<?php
declare (strict_types = 1);

namespace app\mobile\middleware;

use app\common\exception\RequestException;
use app\mobile\traits\ModuleTrait;
use think\facade\Request;
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
        $token = Request::param('token','');
        if(!Auth::guard(self::$module_name)->token($token)->check()){
            throw new RequestException('登录过期，请重新登录！',40101);
        }

        return $next($request);

    }
}

<?php
declare (strict_types = 1);

namespace app\admin\middleware;

use app\common\exception\SystemNotInstallException;
use Tools\Responses;

class CommonMiddleware
{


    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Responses
     */
    public function handle($request, \Closure $next)
    {

        //判断是否安装
        if(!file_exists(env('root_path').'data/install._lock')){
            throw new SystemNotInstallException('请先安装应用！',50003);
        }

        return $next($request);

    }
}

<?php



namespace app\common\middleware;

use Tools\AdminPermission;
use think\Exception;
use think\facade\Session;

class AdminPermissionMiddleware
{
    public function handle($request, \Closure $next,$nodes)
    {
        $role_id = Session::get('role_id');

        $adminPermission = new AdminPermission();
        $nodes = explode('@',$nodes);
        $adminPermission->setRoleId($role_id);
        $adminPermission->setModuleName($nodes[0]);
        $adminPermission->setActionName($nodes[1]);

        //检查操作权限
        if(!$adminPermission->checkActionAccess()){
            throw new Exception('权限不足！',40301);
        }

        return $next($request);
    }
}

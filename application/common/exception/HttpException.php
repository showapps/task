<?php
/**
 * HttpException.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/2
 */

namespace app\common\exception;


use Exception;
use think\exception\Handle;
use think\exception\PDOException;
use think\exception\RouteNotFoundException;
use think\facade\Request;
use Tools\Responses;

class HttpException extends Handle
{
    public function render(Exception $e)
    {
        // 添加自定义异常处理机制
        $status = $e->getCode();
        $message = $e->getMessage();

        if((!$status) && method_exists($e,'getStatusCode')){
            $status = $e->getStatusCode();
        }

        if ($e instanceof RouteNotFoundException){
            $status = 40401;
            $message = '页面不存在~';
        }

        if ($e instanceof PDOException ){
            $status = 50002;
            $message = '操作失败：'.$message;
        }

        $status = $status == 0 ? 50001 : $status;
        if(Request::ext() == 'json'){
            return Responses::data($status, $message);
        }else{

            if($e instanceof MobileErrorException){
                return view('index@mobile/error',[
                    'message'=>$message,
                ]);
            }

            if(stripos(Request::url(),'/admin') === 0){

                if($status == 40101){
                    return response('<html><head><script >top.location.href = "'.admin_url('login').'";</script></head></html>',200);
                }else if($status == 40301){
                    return response('<html><head><script >top.location.href = "'.admin_url('index').'";</script></head></html>',200);
                }

                if($e instanceof SystemNotInstallException){
                    return response('<html><head><script >top.location.href = "'.url('/install/index').'";</script></head></html>',200);
                }

            }

            return response($message);
        }

        // 其他错误交给系统处理
        return parent::render($e);
    }

}
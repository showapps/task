<?php
/**
 * AuthController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/8
 */

namespace app\admin\controller;


use app\admin\traits\ModuleTrait;
use app\admin\validate\LoginValidate;
use app\common\exception\DbException;
use app\common\exception\RequestException;
use app\common\model\TokenModel;
use app\common\model\UserModel;
use think\facade\Request;
use think\facade\Session;
use Tools\Auth;
use Tools\Responses;

class AuthController
{

    use ModuleTrait;

    protected $directory = 'auth';

    public function index()
    {

        //清空 token
        $token = Session::get('admin_auth_token');
        TokenModel::where('token','=',$token)->delete();

        Session::set('admin_auth_token','');
        Session::set('admin_auth_info',[]);
        Session::set('role_id',0);
        return view($this->directory . '/index');

    }



    /**
     * 账户登录
     *
     * @return Responses
     * */
    public function login()
    {

        //表单验证
        $validate = new LoginValidate();
        $vResult = $validate->scene('account')->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $account = Request::post('account','','trim');
        $password = Request::post('password','','trim');

        //获取用户
        $user = UserModel::where('(user_name=\''.$account.'\' OR phone=\''.$account.'\')')
            ->where('password','=',password_encrypt($password))
            ->find();

        if(!$user){
            throw new RequestException('账号或密码错误',40003);
        }

        if($user['is_admin'] != 100){
            throw new RequestException('您不是平台管理账户',40003);
        }

        try {

            //创建Token
            $token = Auth::guard(self::$module_name)->createToken($user,[],86400 * 7);
            if(!$token){
                throw new DbException('登录失败',50001);
            }

            //销毁其他 token
            /*
            TokenModel::where('user_id','=',$user['id'])
                ->where('guard','=',self::$module_name)
                ->where('token','<>',$token['token'])
                ->delete();
            */

            Session::set('admin_auth_token',$token['token']);
            Session::set('admin_captcha_image','');
            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


}
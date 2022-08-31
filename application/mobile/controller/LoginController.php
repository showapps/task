<?php


namespace app\mobile\controller;


use app\common\exception\DbException;
use app\common\exception\RequestException;
use app\common\model\TokenModel;
use app\common\model\UserModel;
use app\mobile\traits\ModuleTrait;
use app\mobile\validate\LoginValidate;
use think\facade\Request;
use Tools\Auth;
use Tools\Responses;

class LoginController
{

    use ModuleTrait;



    /**
     * 手机登录
     *
     * @return Responses
     * */
    public function phone()
    {

        //表单验证
        $validate = new LoginValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $phone = Request::post('phone','','trim');
        $share_code = Request::post('share_code','','trim');

        //获取用户
        $user = UserModel::where('phone','=',$phone)->find();

        if(!$user){
            //执行注册
            $user = UserModel::register($phone,$share_code);
            if(!$user){
                throw new RequestException('手机注册失败',40003);
            }
        }

        //判断封禁
        if($user['status'] != 1){
            throw new RequestException('账号已被平台封禁，请联系客服解冻',40003);
        }

        //禁止登陆
        if(in_black($user['id'],1001)){
            throw new RequestException('平台禁止您登陆，请联系客服解冻',40003);
        }

        try {

            //创建Token
            $token = Auth::guard(self::$module_name)->createToken($user,[],86400 * 7);
            if(!$token){
                throw new DbException('登录失败',50001);
            }

            $token['is_wechat'] = $user['is_wechat'];

            //销毁短信验证码
            delete_sms_code(Request::post('captcha_sms.id'));
            return Responses::data(200, 'success',$token);

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }

}
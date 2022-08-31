<?php
/**
 * MeController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/8
 */

namespace app\admin\controller;


use app\admin\traits\AuthTrait;
use app\admin\validate\MeUpdateValidate;
use app\admin\validate\PasswordUpdateValidate;
use app\common\exception\DbException;
use app\common\exception\RequestException;
use app\common\model\TokenModel;
use app\common\model\UserModel;
use think\facade\Hook;
use think\facade\Request;
use Tools\Responses;

class MeController
{

    use AuthTrait;

    protected $directory = 'me';

    public function __construct()
    {
        $this->initAuthInfo();
    }



    public function passwordView()
    {
        return view($this->directory . '/password');
    }


    /**
     * 修改密码
     *
     * @return Responses
     * */
    public function password()
    {

        //表单验证
        $validate = new PasswordUpdateValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $password = password_encrypt(Request::post('password','','trim'));
        $npassword = password_encrypt(Request::post('npassword','','trim'));

        if(self::$user['password'] != $password){
            throw new DbException('密码错误',40003);
        }

        try {

            //更新密码
            $result = UserModel::where('id','=',self::$user_id)->update([
                'password'=> $npassword,
                'updated_at'=> date('Y-m-d H:i:s'),
            ]);

            if(!$result){
                throw new DbException('修改失败',50001);
            }

            //销毁全部 token
            TokenModel::where('user_id','=',self::$user_id)->delete();

            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }



    public function settingView()
    {
        $user = self::$user;
        return view($this->directory . '/setting',compact('user'));
    }


    /**
     * 修改资料
     *
     * @return Responses
     * */
    public function setting()
    {

        //表单验证
        $validate = new MeUpdateValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $data = [];
        $data['user_name'] = Request::post('user_name','','trim');
        $data['nick_name'] = Request::post('nick_name','','trim,strip_tags,htmlspecialchars');
        $data['true_name'] = Request::post('true_name','','trim,strip_tags,htmlspecialchars');
        $data['updated_at'] = date('Y-m-d H:i:s');

        //用户名唯一
        if(self::$user['user_name'] != $data['user_name']){
            $userCount = UserModel::where('user_name','=',$data['user_name'])->count();
            if($userCount >= 1){
                throw new DbException('新用户名已被占用',40003);
            }
        }

        try {

            //资料更新
            $result = UserModel::where('id','=',self::$user_id)->update($data);

            if(!$result){
                throw new DbException('修改失败',50001);
            }

            //资料更新 事件
            Hook::listen('user_update',UserModel::where('id','=',self::$user_id)->find());
            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage(),[UserModel::getLastSql()]);
        }

    }


}
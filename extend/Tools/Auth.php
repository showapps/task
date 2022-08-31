<?php


namespace Tools;


use app\common\model\TokenModel;

class Auth
{
    private static $instances = [];
    private $model = null;
    private $token = null;
    private $guard = '';
    private $user = [];
    private $id = 0;
    private $tokenInfo = [];

    private function __construct()
    {

    }



    /**
     * 守卫
     *
     * @param  string  $guard
     * @return Auth
     */
    public static function guard($guard)
    {

        if(!isset(self::$instances[$guard])){
            self::$instances[$guard] = null;
        }

        if (!(self::$instances[$guard] instanceof self)){
            self::$instances[$guard] = new self();
            self::$instances[$guard]->guard = $guard;
            self::$instances[$guard]->model = config('user.'.$guard)['model'];
        }

        return self::$instances[$guard];
    }



    /**
     * 设置token
     *
     * @param  string  $guard
     * @return Auth
     */
    public function token($token)
    {
        $this->token = $token;
        return $this;
    }


    /**
     * 登录检查
     *
     * @return bool
     */
    public function check(){

        $info = TokenModel::where('guard','=',$this->guard)
            ->where('token','=',$this->token)
            ->where('end_dt','>',date('Y-m-d H:i:s'))
            ->find();

        if(!$info){
            return false;
        }

        $User = new $this->model;
        $user = $User->where('id',$info->user_id)->find();

        if(!$user){
            return false;
        }

        $this->user = $user;
        $this->id = $user->id;
        $this->tokenInfo = $info;

        return true;
    }



    /**
     * 登录用户
     *
     * @return array
     */
    public function user()
    {
        if(!$this->user){
            $this->check();
        }

        return $this->user;
    }



    /**
     * 登录用户编号
     *
     * @return int
     */
    public function id()
    {
        if(!$this->user){
            $this->check();
        }

        return $this->id;
    }



    /**
     * 创建Token
     *
     * @param  array  $user
     * @param  array  $info 授权信息
     * @param  int  $expire 有效秒数
     * @return array
     */
    public function createToken($user,$info = [],$expire = 86400)
    {
        $token = TokenModel::create([
            'guard'=>$this->guard,
            'user_id'=>$user->id,
            'token'=>md5(md5($this->guard.'&'.$user->id.'&'.'_'.uniqid().'_'.mt_rand(111,999))),
            'info'=>$info,
            'expire'=>$expire,
            'end_dt'=>date('Y-m-d H:i:s',time()+$expire),
        ]);

        if(!$token){
            return [];
        }

        $this->user = $user;

        return $token;
    }






    /**
     * token 信息
     *
     * @return TokenModel
     */
    public function tokenInfo()
    {
        if(!$this->tokenInfo){
            $this->check();
        }

        return $this->tokenInfo;
    }



    /**
     * token 销毁
     *
     * @return bool
     */
    public function destroy(){

        return (bool)TokenModel::where('guard','=',$this->guard)
            ->where('token','=',$this->token)
            ->delete();
    }
}
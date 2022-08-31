<?php
/**
 * WechatBindingController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/11
 */

namespace app\mobile\controller;


use app\common\exception\DbException;
use app\common\exception\MobileErrorException;
use app\common\model\UserModel;
use app\common\model\UserWechatModel;
use app\mobile\traits\AuthTrait;
use iHexiang\Requests\Requests;
use think\Db;
use think\Exception;
use think\facade\Request;
use think\facade\Session;
use Tools\Auth;
use Tools\DbConfig;
use Tools\Responses;

class WechatBindingController
{

    use AuthTrait;

    protected $config = [];

    public function __construct()
    {
        $this->config = dbConfig('mobile_mp',[]);
        if((!$this->config) || (!isset($this->config['app_id'])) || (!$this->config['app_id'])){
            throw new MobileErrorException( '未开启微信公众号授权功能',40401);
        }
    }


    public function test()
    {
        $redirect_uri = url('/api/mobile/wechat/test/callback',[],true,true).'?_v='.time();
        $url = url('/api/mobile/wechat/binding',[],true,true).'?user_id=2&redirect_uri='.urlencode($redirect_uri);
        //return $url;
        return response('<html><head><script >top.location.href = "'.$url.'";</script></head></html>',200);
    }

    /**
     * 微信绑定
     * */
    public function binding()
    {

        $share_code = Request::get('share_code','');
        $redirect_uri = Request::get('redirect_uri','');

        if(!$redirect_uri){
            throw new MobileErrorException( '非法的页面访问',40003);
        }

        $state = md5(time().mt_rand(111,999).mt_rand(111,999));
        Session::set('wechat_redirect_uri',$redirect_uri);
        Session::set('wechat_share_code',$share_code);
        Session::set('wechat_login_state',$state);

        $redirect_uri = url('/api/mobile/wechat/callback',[],true,true);
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->config['app_id'].'&redirect_uri='.urlencode($redirect_uri).'&response_type=code&scope=snsapi_userinfo&state='.$state.'#wechat_redirect';

        return response('<html><head><script >top.location.href = "'.$url.'";</script></head></html>',200);

    }



    /**
     * 微信回调
     * */
    public function callback()
    {

        $redirect_uri = Session::get('wechat_redirect_uri');
        $share_code = (string)Session::get('wechat_share_code');

        $code = Request::get('code','');
        if(!$code){
            $redirect_uri = $redirect_uri . '?action=error&message=' . urlencode('授权失败：非法的页面访问..');
            return response('<html><head><script >top.location.href = "'.$redirect_uri.'";</script></head></html>',200);
        }

        $state = Request::get('state','');
        if($state != Session::get('wechat_login_state')){
            $redirect_uri = $redirect_uri . '?action=error&message=' . urlencode('授权失败：非法的页面访问...');
            return response('<html><head><script >top.location.href = "'.$redirect_uri.'";</script></head></html>',200);
        }

        //换取 access_token
        $result = Requests::post('https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->config['app_id'].'&secret='.$this->config['app_secret'].'&code='.$code.'&grant_type=authorization_code');
        if($result->http_code != 200){
            $redirect_uri = $redirect_uri . '?action=error&message=' . urlencode('微信授权失败');
            return response('<html><head><script >top.location.href = "'.$redirect_uri.'";</script></head></html>',200);
        }

        $result = $result->toArray('json');
        if(!$result['access_token']){
            $redirect_uri = $redirect_uri . '?action=error&message=' . urlencode('微信授权失败.');
            return response('<html><head><script >top.location.href = "'.$redirect_uri.'";</script></head></html>',200);
        }

        $result = Requests::post('https://api.weixin.qq.com/sns/userinfo?access_token='.$result['access_token'].'&openid='.$result['openid'].'&lang=zh_CN');
        if($result->http_code != 200){
            $redirect_uri = $redirect_uri . '?action=error&message=' . urlencode('微信授权失败..');
            return response('<html><head><script >top.location.href = "'.$redirect_uri.'";</script></head></html>',200);
        }

        $result = $result->toArray('json');
        if(!$result['openid']){
            $redirect_uri = $redirect_uri . '?action=error&message=' . urlencode('微信授权失败...');
            return response('<html><head><script >top.location.href = "'.$redirect_uri.'";</script></head></html>',200);
        }

        $wechat = UserWechatModel::where('mp_open_id','=',$result['openid'])->find();

        //判断未注册
        if(!$wechat){
            //自动注册
            $regResult = UserModel::mpRegister($result,$share_code);
            if(!$regResult[0]){
                $redirect_uri = $redirect_uri . '?action=error&message=' . urlencode($regResult[1]);
                return response('<html><head><script >top.location.href = "'.$redirect_uri.'";</script></head></html>',200);
            }
            $user = $regResult[1];
        }else{
            $user = UserModel::where('id','=',$wechat['user_id'])->find();
        }

        try {

            //创建Token
            $token = Auth::guard(self::$module_name)->createToken($user,[],86400 * 7);
            if(!$token){
                $redirect_uri = $redirect_uri . '?action=error&message=' . urlencode('登录失败...');
                return response('<html><head><script >top.location.href = "'.$redirect_uri.'";</script></head></html>',200);
            }

            $redirect_uri = $redirect_uri . '?action=success&token='.$token['token'].'&user_id='.$user['id'];
            return response('<html><head><script >top.location.href = "'.$redirect_uri.'";</script></head></html>',200);

        }catch (DbException $e){
            $redirect_uri = $redirect_uri . '?action=error&message=' . urlencode($e->getMessage());
            return response('<html><head><script >top.location.href = "'.$redirect_uri.'";</script></head></html>',200);
        }

    }




}
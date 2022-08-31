<?php
/**
 * MiniAppController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/9/15
 */

namespace app\mobile\controller;


use app\common\model\TokenModel;
use app\common\model\UserModel;
use app\common\model\UserWechatModel;
use app\mobile\traits\AuthTrait;
use app\mobile\validate\WechatDecodesValidate;
use iHexiang\Requests\Requests;
use think\Db;
use think\Exception;
use think\facade\Request;
use Tools\Responses;
use WechatDecode\DataCrypt;

class MiniAppController
{

    use AuthTrait;

    public function __construct()
    {
        $this->initAuthInfo();
    }




    /**
     * 绑定微信
     * */

    public function binding()
    {

        $code = Request::post('code','');
        if(!$code){
            throw new Exception('code 参数错误',40004);
        }

        //$config = dbConfig('MINI_APP',[]);
        $config = [
            'app_id'=>'wx3a2da3d20dd0fb2b',
            'app_secret'=>'90ad680d55a1c09ddfdc00f1bb6634dc',
        ];
        if(!$config){
            return Responses::data(50001,'设置失败:暂未开启小程序功能..');
        }

        //组装微信登录参数
        $param = [
            'appid' => $config['app_id'],
            'secret' => $config['app_secret'],
            'js_code' => $code,
            'grant_type' =>'authorization_code'
        ];

        $response = Requests::post('https://api.weixin.qq.com/sns/jscode2session',[],$param);
        if(!$response->http_code){
            throw new Exception('授权失败',40004);
        }

        $wxInfo = $response->toArray('json');

        //检查微信登录返回
        if(!isset($wxInfo['session_key'])){
            throw new Exception('授权失败.',40004);
        }

        $validate = new WechatDecodesValidate();
        if(!$validate->check(Request::post())) {
            throw new Exception($validate->getError(),40004);
        }

        $encryptedData = Request::post('encryptedData','');
        $iv = Request::post('iv','');

        //数据解密
        $pc = new DataCrypt($config['app_id'], $wxInfo['session_key']);
        $json = '';
        $errCode = $pc->decryptData($encryptedData, $iv, $json );

        //解密失败
        if($errCode != 0){
            throw new Exception('信息获取失败.',400);
        }

        $wxdata = json_decode($json,true);

        //信息获取失败
        if((!$wxdata) || (!isset($wxdata['openId']))){
            throw new Exception('信息获取失败..',400);
        }

        if(self::$user['is_wechat'] == 1){
            throw new Exception('绑定失败：账户已绑定其他微信..',400);
        }

        $open_id = $wxdata['openId'];
        //open_id 已绑定其他账户
        $wechat = UserWechatModel::where('miniapp_open_id','=',$open_id)->find();
        if($wechat){
            throw new Exception('绑定失败：微信已绑定其他账户..',400);
        }

        $user_data = [];
        $data = [];
        $data['user_id'] = self::$user_id;
        $data['mp_open_id'] = get_rand_union_id();
        $data['miniapp_open_id'] = $open_id;
        $data['app_open_id'] = get_rand_union_id();
        $data['union_id'] = isset($wxdata['unionId']) && $wxdata['unionId'] ? $wxdata['unionId'] : get_rand_union_id();

        //昵称
        if(isset($wxdata['nickName']) && $wxdata['nickName']){
            $data['nick_name'] = $wxdata['nickName'];
            $user_data['nick_name'] = $wxdata['nickName'];
        }

        //头像
        if(isset($wxdata['avatarUrl']) && $wxdata['avatarUrl']){
            $data['avatar'] = $wxdata['avatarUrl'];
            $user_data['avatar'] = $wxdata['avatarUrl'];
        }

        //性别
        if(isset($wxdata['gender']) && $wxdata['gender']){
            $data['gender'] = $wxdata['gender'];
            $user_data['gender'] = $wxdata['gender'];
        }

        $user_data['updated_at'] = date('Y-m-d H:i:s');
        $user_data['is_wechat'] = 1;

        Db::startTrans();
        try{

            UserWechatModel::create($data);
            UserModel::where('id','=',self::$user_id)->update($user_data);
            Db::commit();
            return Responses::data(200,'success');

        }catch (\Exception $e){

            Db::rollback();
            return Responses::data(50101,'绑定失败');
        }

    }



}
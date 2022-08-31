<?php
/**
 * MpWechatController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/24
 */

namespace app\mobile\controller;

use think\facade\Cache;
use think\facade\Request;
use Tools\Responses;
use Tools\Wechat;

class MpWechatController
{

    private $mpConfig = [];
    private $appId = '';
    private $appSecret = '';

    public function __construct()
    {

        $this->mpConfig = dbConfig('mobile_mp',[]);
        $this->appId = $this->mpConfig['app_id'];
        $this->appSecret = $this->mpConfig['app_secret'];
    }


    public function config(){

        $jsapiTicket = $this->getJsapiTicket();

        //不对url进行过滤
        $url = Request::post('url');

        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        $string = "jsapi_ticket={$jsapiTicket}&noncestr={$nonceStr}&timestamp={$timestamp}&url={$url}";
        $signature = sha1($string);
        $data = array(
            "debug"     => 1,
            "appId"     => $this->appId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );



        return Responses::data(200,'success',$data,compact('jsapiTicket','nonceStr','timestamp','url'));

    }



    /**

     * 添加微信分享接口

     * 第二步：用第一步拿到的access_token 采用http GET方式请求获得jsapi_ticket

     */

    public function getJsapiTicket(){

        $ticketCache = Cache::get('wx_gzh_ticket',['end_time'=>time() - 1]);
        if((!$ticketCache) || (!isset($ticketCache['end_time'])) || $ticketCache['end_time'] < time() ){

            //$token = Wechat::gzhAccessToken(dbConfig('wechat_gzh',[]));
            $token = Wechat::gzhAccessToken($this->mpConfig);

            $res = file_get_contents("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$token."&type=jsapi");
            $res = json_decode($res, true);
            $ticket = $res['ticket'];
            $data = ['ticket'=>$ticket,'end_time'=>time() + 7000];
            Cache::set('wx_gzh_ticket',$data);
        }else{
            $ticket = $ticketCache['ticket'];
        }

        return $ticket;

    }

    //创建随机字符
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

}
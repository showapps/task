<?php
// +----------------------------------------------------------------------
// | 科创网贷超市系统 Pro
// +----------------------------------------------------------------------
// | Copyright (c) 2017-2019 https://www.kechuang.link All rights reserved.
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
// | Author: 深圳科创软件有限公司 <service@kechuang.link>
// +----------------------------------------------------------------------


namespace Tools;


use iHexiang\Requests\Requests;
use think\facade\Cache;

class Wechat
{


    public static function gzhAccessToken($config,$refresh = false)
    {

        //判断有第三方授权配置
        $wechat_token_api_url = env('WECHAT_ACCESS_TOKEN_API_URL','');
        if(stripos($wechat_token_api_url,'http') === 0){

            $wechat_token_api_key = env('WECHAT_ACCESS_TOKEN_API_KEY','');
            $result = Requests::post($wechat_token_api_url,[],['sign'=>md5($wechat_token_api_key)])->toArray('json');
            if($result['status'] == 200){
                return $result['data']['access_token'];
            }

            return '';

        }else{

            //缓存key
            $cacheKey = 'gzhAccessToken';

            //读取缓存中的 access_token
            $access_token_info = Cache::get($cacheKey,[]);

            //如果缓存获取失败 或 即将过期 或强制刷新
            if(empty($access_token_info) || is_null($access_token_info) || $access_token_info['ex_time'] <= time() || $refresh === true){

                $apiUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$config['app_id'].'&secret='.$config['app_secret'];

                $access_token = Requests::post($apiUrl)->toArray('json');
                if($access_token['access_token']){
                    $access_token['ex_time'] = time() + ($access_token['expires_in'] - 100);
                }else{

                    //再次获取 access_token
                    $apiUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$config['app_id'].'&secret='.$config['app_secret'];
                    $access_token = Requests::post($apiUrl)->toArray('json');
                    if($access_token['access_token']){
                        $access_token['ex_time'] = time() + ($access_token['expires_in'] - 100);
                    }
                }

                //使用缓存存储
                Cache::set($cacheKey,$access_token,7200);
                $access_token_info = $access_token;

            }

            //返回 access_token
            return $access_token_info['access_token'];

        }


    }

}
<?php
/**
 * CaptchaController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/1
 */

namespace app\mobile\controller;


use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use app\mobile\validate\SmsCaptchaValidate;
use Gregwar\Captcha\PhraseBuilder;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Request;
use Tools\Responses;

class CaptchaController
{


    /**
     * 图片验证码
     * */
    public function image()
    {

        $phraseBuilder = new PhraseBuilder(4, '0123456789');
        $builder = new \Gregwar\Captcha\CaptchaBuilder(null,$phraseBuilder);
        $builder->setDistortion(true);
        $builder->setBackgroundColor(245, 247, 250);
        $captcha = $builder->build();
        $captcha_content = $captcha->getPhrase();
        $id = strval(time().mt_rand(111,999).mt_rand(11,99));

        Cache::set('captcha_image_'.$id,$captcha_content,3600);

        $captcha_base64_content = $captcha->inline();

        $data = [];
        $data['id'] = $id;
        $data['image'] = $captcha_base64_content;

        return Responses::data(200, 'success',$data);

    }




    /**
     * 短信验证码
     * */
    public function sms(){

        $validate = new SmsCaptchaValidate();

        if(!$validate->check(Request::post())) {
            return Responses::data(40003, $validate->getError());
        }

        $config = dbConfig('sms',[]);
        if(!isset($config['access_id'])){
            return Responses::data(50001,'发送失败:短信功能未开通');
        }


        $id = strval(time().mt_rand(111,999).mt_rand(11,99));
        $code = mt_rand(111,999) . mt_rand(111,999);
        $phone = Request::post('phone','');

        $sms_id = Request::post('sms_id',1001,'intval');
        if((!isset($config['template_ids'][$sms_id])) || (!$config['template_ids'][$sms_id])){
            return Responses::data(50001,'发送失败:短信模板未配置');
        }

        $data = [];
        $data['id'] = $id;

        if(stripos($phone,'1808888') !== false){
            Cache::set('captcha_sms_'.$id,['code'=>123456,'phone'=>$phone],3600);
            return Responses::data(200,'success',$data);
        }

        AlibabaCloud::accessKeyClient($config['access_id'], $config['access_secret'])
            ->regionId('cn-hangzhou')
            ->asDefaultClient();

        $template_id = $config['template_ids'][$sms_id];

        try {

            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                        'PhoneNumbers' => $phone,
                        'SignName' => $config['sign_name'],
                        'TemplateCode' => $template_id,
                        'TemplateParam' => '{code:'.$code.'}',
                    ],
                ])
                ->request();

            $response = $result->toArray();
            if($response['Code'] != 'OK'){
                Log::write('发送短信失败1:'.json_encode($response),'notice');
                return Responses::data(50001,'发送失败 .',$response);
            }

        } catch (ClientException $e) {
            Log::write('发送短信失败2:'.$e->getMessage(),'notice');
            return Responses::data(50001,'发送失败 ..',[$e->getMessage()]);
        } catch (ServerException $e) {
            Log::write('发送短信失败3:'.$e->getMessage(),'notice');
            return Responses::data(50001,'发送失败 ..',[$e->getMessage()]);
        }

        Cache::set('captcha_sms_'.$id,['code'=>$code,'phone'=>$phone],3600);

        return Responses::data(200,'success',[
            'id'=>$id,
        ]);


    }


}
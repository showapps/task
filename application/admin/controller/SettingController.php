<?php
/**
 * SettingController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/25
 */

namespace app\admin\controller;


use app\admin\traits\AuthTrait;
use app\admin\validate\SettingBasicValidate;
use app\admin\validate\SettingCommissionValidate;
use app\admin\validate\SettingFriendAwardValidate;
use app\admin\validate\SettingMoneyValidate;
use app\admin\validate\SettingNoviceRewardValidate;
use app\admin\validate\SettingPaymentValidate;
use app\admin\validate\SettingPerformanceValidate;
use app\admin\validate\SettingRankingValidate;
use app\admin\validate\SettingRefreshNumberValidate;
use app\admin\validate\SettingSmsValidate;
use app\admin\validate\SettingUploadValidate;
use app\admin\validate\SettingUserValidate;
use app\admin\validate\SettingVipValidate;
use app\admin\validate\SettingWithdrawValidate;
use app\common\exception\RequestException;
use app\common\model\ConfigModel;
use app\common\model\NoviceRewardModel;
use app\common\model\RefreshSkuModel;
use app\common\model\VipSkuModel;
use think\facade\Request;
use Tools\DbConfig;
use Tools\Responses;

class SettingController
{

    use AuthTrait;

    protected $directory = 'setting';

    public function __construct()
    {
        $this->initAuthInfo();
        clear_db_config_cache();
    }


    public function basic_view(){

        $configs = [];

        //网站信息
        $configs['site_logo'] = dbConfig('site.logo','');
        $configs['site_mobile_logo'] = dbConfig('site.logo','');
        $configs['site_name'] = dbConfig('site.name','');
        $configs['site_domain'] = dbConfig('site.domain','');

        //数据库信息
        $configs['db_type'] = dbConfig('db.type','mysql');
        $configs['db_hostname'] = dbConfig('db.hostname','127.0.0.1');
        $configs['db_hostport'] = dbConfig('db.hostport',3306);
        $configs['db_database'] = dbConfig('db.database','');
        $configs['db_username'] = dbConfig('db.username','');
        $configs['db_password'] = dbConfig('db.password','');

        //缓存信息
        $configs['cache_driver'] = dbConfig('cache.driver','redis');
        $configs['cache_host'] = dbConfig('cache.host','127.0.0.1');
        $configs['cache_port'] = dbConfig('cache.port','');
        $configs['cache_password'] = dbConfig('cache.password','');

        //移动端 公众号
        $configs['mobile_mp_switch'] = dbConfig('mobile_mp.switch',false);
        $configs['mobile_mp_link'] = dbConfig('mobile_mp.link','');
        $configs['mobile_mp_app_id'] = dbConfig('mobile_mp.app_id','');
        $configs['mobile_mp_app_secret'] = dbConfig('mobile_mp.app_secret','');

        return view($this->directory .'/basic',compact('configs'));
    }


    public function basic(){

        $datas = [];

        //表单验证
        $validate = new SettingBasicValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        //网站信息
        $datas['site.logo'] = Request::post('site_logo','','trim');
        $datas['site.mobile_logo'] = Request::post('site_mobile_logo','','trim');
        $datas['site.name'] = Request::post('site_name','','trim');
        $datas['site.domain'] = rtrim(Request::post('site_domain','','trim'),'/');

        //数据库信息
        $datas['db.type'] = 'mysql';
        $datas['db.hostname'] = Request::post('db_hostname','','trim');
        $datas['db.hostport'] = Request::post('db_hostport','','trim');
        $datas['db.database'] = Request::post('db_database','','trim');
        $datas['db.username'] = Request::post('db_username','','trim');
        $datas['db.password'] = Request::post('db_password','','trim');

        //缓存信息
        $datas['cache.driver'] = 'redis';
        $datas['cache.host'] = Request::post('cache_host','','trim');
        $datas['cache.port'] = Request::post('cache_port','','trim');
        $datas['cache.password'] = Request::post('cache_password','','trim');

        //公众号
        $datas['mobile_mp.switch'] = true;
        $datas['mobile_mp.link'] = rtrim(Request::post('mobile_mp_link','','trim'),'/');
        $datas['mobile_mp.app_id'] = Request::post('mobile_mp_app_id','','trim');
        $datas['mobile_mp.app_secret'] = Request::post('mobile_mp_app_secret','','trim');
        
        $updated_at = date('Y-m-d H:i:s');
        foreach ($datas as $name=>$val){

            $data = [];
            $data['content'] = $val;
            $data['updated_at'] = $updated_at;

            ConfigModel::where('name','=',$name)->update($data);
        }

        $envs = [];
        $envs['SITE_NAME'] = $datas['site.name'];
        $envs['SITE_DOMAIN'] = $datas['site.domain'];
        $envs['DB_TYPE'] = $datas['db.type'];
        $envs['DB_HOSTNAME'] = $datas['db.hostname'];
        $envs['DB_DATABASE'] = $datas['db.database'];
        $envs['DB_USERNAME'] = $datas['db.username'];
        $envs['DB_PASSWORD'] = $datas['db.password'];
        $envs['DB_HOSTPORT'] = $datas['db.hostport'];

        $envs['CACHE_DRIVER'] = $datas['cache.driver'];
        $envs['CACHE_HOST'] = $datas['cache.host'];
        $envs['CACHE_PORT'] = $datas['cache.port'];
        $envs['CACHE_EXPIRE'] = 604800;

        modEnv(env('root_path'),$envs);

        clear_db_config_cache();
        return Responses::data(200,'success');
    }




    public function payment_view(){

        $configs = [];

        //微信支付
        $configs['wechat_payment_switch'] = dbConfig('wechat_payment.switch',false);
        $configs['wechat_payment_mp_app_id'] = dbConfig('wechat_payment.mp_app_id','');
        $configs['wechat_payment_mch_id'] = dbConfig('wechat_payment.mch_id','');
        $configs['wechat_payment_key'] = dbConfig('wechat_payment.key','');

        //证书
        $wx_certificate_dir = env('root_path').'data/wxpay/cert/';
        if(!file_exists($wx_certificate_dir.'cert_client.pem')){
            file_put_contents($wx_certificate_dir.'cert_client.pem','');
        }

        if(!file_exists($wx_certificate_dir.'cert_key.pem')){
            file_put_contents($wx_certificate_dir.'cert_key.pem','');
        }


        $configs['wechat_payment_cert_client'] = file_get_contents($wx_certificate_dir.'cert_client.pem');
        $configs['wechat_payment_cert_key'] = file_get_contents($wx_certificate_dir.'cert_key.pem');

        return view($this->directory .'/payment',compact('configs'));
    }


    public function payment(){

        $datas = [];

        //表单验证
        $validate = new SettingPaymentValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        //微信支付
        $datas['wechat_payment.switch'] = 1;
        $datas['wechat_payment.mp_app_id'] = Request::post('wechat_payment_mp_app_id','','trim');
        $datas['wechat_payment.mch_id'] = Request::post('wechat_payment_mch_id','','trim');
        $datas['wechat_payment.key'] = Request::post('wechat_payment_key','','trim');

        $updated_at = date('Y-m-d H:i:s');
        foreach ($datas as $name=>$val){

            $data = [];
            $data['content'] = $val;
            $data['updated_at'] = $updated_at;

            ConfigModel::where('name','=',$name)->update($data);
        }

        //证书
        $wx_certificate_dir = env('root_path').'data/wxpay/cert/';
        file_put_contents($wx_certificate_dir.'cert_client.pem',Request::post('wechat_payment_cert_client','','trim'));
        file_put_contents($wx_certificate_dir.'cert_key.pem',Request::post('wechat_payment_cert_key','','trim'));


        clear_db_config_cache();
        return Responses::data(200,'success');
    }




    public function sms_view(){

        $configs = [];

        $configs['sms_access_id'] = dbConfig('sms.access_id','');
        $configs['sms_access_secret'] = dbConfig('sms.access_secret','');
        $configs['sms_sign_name'] = dbConfig('sms.sign_name','');
        $configs['sms_template_ids'] = json_decode(dbConfig('sms.template_ids','[]'),true);

        return view($this->directory .'/sms',compact('configs'));
    }


    public function sms(){

        $datas = [];

        //表单验证
        $validate = new SettingSmsValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        //微信支付
        $datas['sms.access_id'] = Request::post('sms_access_id','','trim');
        $datas['sms.access_secret'] = Request::post('sms_access_secret','','trim');
        $datas['sms.sign_name'] = Request::post('sms_sign_name','','trim');

        //处理模板
        $datas['sms.template_ids'] = [];
        $sms_template_ids = Request::post('sms_template_ids',[]);
        if(!isset($sms_template_ids[1001])){
            $datas['sms.template_ids']['1001'] = '';
        }
        $datas['sms.template_ids']['1001'] = trim($sms_template_ids[1001]);
        $datas['sms.template_ids'] = json_encode($datas['sms.template_ids']);

        $updated_at = date('Y-m-d H:i:s');
        foreach ($datas as $name=>$val){

            $data = [];
            $data['content'] = $val;
            $data['updated_at'] = $updated_at;

            ConfigModel::where('name','=',$name)->update($data);
        }

        clear_db_config_cache();
        return Responses::data(200,'success');
    }





    public function upload_view(){

        $configs = [];

        //上传设置
        $configs['oss_access_id'] = dbConfig('oss.access_id','');
        $configs['oss_access_secret'] = dbConfig('oss.access_secret','');
        $configs['oss_endpoint'] = dbConfig('oss.endpoint','');
        $configs['oss_bucket'] = dbConfig('oss.bucket','');
        $configs['oss_domain_header'] = dbConfig('oss.domain_header','');
        //图片
        $configs['oss_image_max_size'] = dbConfig('oss.image_max_size','');
        $configs['oss_image_allow_exts'] = json_decode(dbConfig('oss.image_allow_exts','[]'),true);
        $configs['oss_image_allow_exts'] = implode(',',$configs['oss_image_allow_exts']);

        //视频
        $configs['oss_video_max_size'] = dbConfig('oss.video_max_size','');
        $configs['oss_video_allow_exts'] = json_decode(dbConfig('oss.video_allow_exts','[]'),true);
        $configs['oss_video_allow_exts'] = implode(',',$configs['oss_video_allow_exts']);

        //音频
        $configs['oss_voice_max_size'] = dbConfig('oss.voice_max_size','');
        $configs['oss_voice_allow_exts'] = json_decode(dbConfig('oss.voice_allow_exts','[]'),true);
        $configs['oss_voice_allow_exts'] = implode(',',$configs['oss_voice_allow_exts']);

        return view($this->directory .'/upload',compact('configs'));
    }


    public function upload(){

        $datas = [];

        //表单验证
        $validate = new SettingUploadValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        //上传设置
        $datas['oss.switch'] = true;
        $datas['oss.access_id'] = Request::post('oss_access_id','','trim');
        $datas['oss.access_secret'] = Request::post('oss_access_secret','','trim');
        $datas['oss.endpoint'] = Request::post('oss_endpoint','','trim');
        $datas['oss.bucket'] = Request::post('oss_bucket','','trim');
        $datas['oss.domain_header'] = Request::post('oss_domain_header','','trim');
        $datas['oss.image_max_size'] = Request::post('oss_image_max_size','','intval');
        $datas['oss.image_allow_exts'] = Request::post('oss_image_allow_exts','','trim');
        $datas['oss.image_allow_exts'] = trim($datas['oss.image_allow_exts'],',');
        $datas['oss.image_allow_exts'] = strpos($datas['oss.image_allow_exts'],',') !== false ? explode(',',$datas['oss.image_allow_exts']) : [$datas['oss.image_allow_exts']];
        $datas['oss.image_allow_exts'] = json_encode($datas['oss.image_allow_exts']);

        $datas['oss.video_max_size'] = Request::post('oss_video_max_size','','intval');
        $datas['oss.video_allow_exts'] = Request::post('oss_video_allow_exts','','trim');
        $datas['oss.video_allow_exts'] = trim($datas['oss.video_allow_exts'],',');
        $datas['oss.video_allow_exts'] = strpos($datas['oss.video_allow_exts'],',') !== false ? explode(',',$datas['oss.video_allow_exts']) : [$datas['oss.video_allow_exts']];
        $datas['oss.video_allow_exts'] = json_encode($datas['oss.video_allow_exts']);

        $datas['oss.voice_max_size'] = Request::post('oss_voice_max_size','','intval');
        $datas['oss.voice_allow_exts'] = Request::post('oss_voice_allow_exts','','trim');
        $datas['oss.voice_allow_exts'] = trim($datas['oss.voice_allow_exts'],',');
        $datas['oss.voice_allow_exts'] = strpos($datas['oss.voice_allow_exts'],',') !== false ? explode(',',$datas['oss.voice_allow_exts']) : [$datas['oss.voice_allow_exts']];
        $datas['oss.voice_allow_exts'] = json_encode($datas['oss.voice_allow_exts']);

        $updated_at = date('Y-m-d H:i:s');
        foreach ($datas as $name=>$val){

            $data = [];
            $data['content'] = $val;
            $data['updated_at'] = $updated_at;

            ConfigModel::where('name','=',$name)->update($data);
        }

        clear_db_config_cache();
        return Responses::data(200,'success');
    }





    public function vip_view(){

        $merchantSkuList = VipSkuModel::where('type','=',1)
            ->order('sort DESC,id DESC')
            ->select();

        if($merchantSkuList){
            foreach ($merchantSkuList as &$row){
                $row['original_price'] = fen_to_float($row['original_price']);
                $row['price'] = fen_to_float($row['price']);
            }
        }

        $configs['vip_user_vip_threshold'] = dbConfig('vip.user_vip_threshold',0);

        //活动
        $configs['activity_rec_money'] = fen_to_float(dbConfig('activity.rec_money',0));
        $configs['activity_rec_sc_rate'] = fen_to_float(dbConfig('activity.rec_sc_rate',0));
        $configs['activity_rec_merchant_sc_rate'] = fen_to_float(dbConfig('activity.rec_merchant_sc_rate',0));
        $configs['activity_rec_user_sc_rate'] = fen_to_float(dbConfig('activity.rec_user_sc_rate',0));

        //提现费率
        $configs['withdraw_sc_rate'] = fen_to_float(dbConfig('withdraw.sc_rate',0));
        $configs['withdraw_user_sc_rate'] = fen_to_float(dbConfig('withdraw.user_sc_rate',0));
        $configs['withdraw_merchant_sc_rate'] = fen_to_float(dbConfig('withdraw.merchant_sc_rate',0));

        return view($this->directory .'/vip',compact('merchantSkuList','configs'));

    }


    public function vip(){

        //表单验证
        $validate = new SettingVipValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $configs = [];

        $configs['vip.user_vip_threshold'] = Request::post('vip_user_vip_threshold',0,'intval');

        //活动
        $configs['activity.rec_money'] = fen_to_int(Request::post('activity_rec_money',0,'floatval'));
        $configs['activity.rec_sc_rate'] = fen_to_int(Request::post('activity_rec_sc_rate',0,'floatval'));
        $configs['activity.rec_merchant_sc_rate'] = fen_to_int(Request::post('activity_rec_merchant_sc_rate',0,'floatval'));
        $configs['activity.rec_user_sc_rate'] = fen_to_int(Request::post('activity_rec_user_sc_rate',0,'floatval'));

        //提现费率
        $configs['withdraw.sc_rate'] = fen_to_int(Request::post('withdraw_sc_rate',0,'floatval'));
        $configs['withdraw.user_sc_rate'] = fen_to_int(Request::post('withdraw_user_sc_rate',0,'floatval'));
        $configs['withdraw.merchant_sc_rate'] = fen_to_int(Request::post('withdraw_merchant_sc_rate',0,'floatval'));

        $updated_at = date('Y-m-d H:i:s');
        foreach ($configs as $name=>$val){

            $data = [];
            $data['content'] = $val;
            $data['updated_at'] = $updated_at;

            ConfigModel::where('name','=',$name)->update($data);
        }
        clear_db_config_cache();

        $datas = Request::post('datas',[]);
        $ids = [];
        if($datas){
            $i = 0;
            foreach ($datas as $key=>$row){
                $data = [];
                $data['type'] = 1;
                $data['title'] = trim(htmlspecialchars(strip_tags($row['title'])));
                $data['month'] = abs(intval($row['title']));
                $data['refresh_number'] = abs(intval($row['refresh_number']));
                $data['original_price'] = fen_to_int(abs(floatval($row['original_price'])));
                $data['price'] = fen_to_int(abs(floatval($row['price'])));
                $data['sort'] = 1000 - $i;
                $data['status'] = 1;

                $id = isset($row['id']) && $row['id'] >= 1 ? $row['id'] : 0;
                if($id >= 1){
                    $data['updated_at'] = date('Y-m-d H:i:s');
                    $result = VipSkuModel::where('id','=',$id)->update($data);
                    if($result){
                        $ids[] = $id;
                    }
                }else{
                    $result = VipSkuModel::create($data);
                    if($result){
                        $ids[] = $result['id'];
                    }
                }

                $i ++;
            }
        }

        VipSkuModel::whereNotIn('id',$ids)->delete();

        return Responses::data(200,'success');
    }


    public function userView(){

        $configs = [];
        //代理费用
        $configs['agency_first.money'] = fen_to_float(dbConfig('agency_first.money',0));
        $configs['agency_high.money'] = fen_to_float(dbConfig('agency_high.money',0));
        $configs['agency_high.spread_agency'] = dbConfig('agency_high.spread_agency',0);
        $configs['agency_high.spread_company_level'] = dbConfig('agency_high.spread_company_level',0);

        //区域代理
        $configs['district_agency.register'] = dbConfig('district_agency.register',false);
        $configs['district_agency.money'] = fen_to_float(dbConfig('district_agency.money',0));

        //运营商
        $configs['operator.register'] = dbConfig('operator.register',false);
        $configs['operator.money'] = fen_to_float(dbConfig('operator.money',0));

        //大区代理
        $configs['big_agency.register'] = dbConfig('big_agency.register',false);
        $configs['big_agency.money'] = fen_to_float(dbConfig('big_agency.money',0));

        return view($this->directory .'/user',compact('configs'));
    }


    public function user(){

        $datas = [];

        //表单验证
        $validate = new SettingUserValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        //代理
        $datas['agency_first.money'] = fen_to_int(Request::post('agency_first_money',0,'floatval,abs'));
        $datas['agency_high.money'] = fen_to_int(Request::post('agency_high_money',0,'floatval,abs'));
        $datas['agency_high.spread_agency'] = Request::post('agency_high_spread_agency',0,'intval,abs');
        $datas['agency_high.spread_company_level'] = Request::post('agency_high_spread_company_level',0,'intval,abs');
        $datas['agency_high.spread_company_level'] = dbConfig('agency_high.spread_company_level',0);

        //区域代理
        $datas['district_agency.register'] = Request::post('district_agency_register',2,'intval') == 1 ? 1 : 2;
        $datas['district_agency.money'] = fen_to_int(Request::post('district_agency_money',0,'floatval,abs'));

        //运营商
        $datas['operator.register'] = Request::post('operator_register',2,'intval') == 1 ? 1 : 2;
        $datas['operator.money'] = fen_to_int(Request::post('operator_money',0,'floatval,abs'));

        //大区代理
        $datas['big_agency.register'] = Request::post('big_agency_register',2,'intval') == 1 ? 1 : 2;
        $datas['big_agency.money'] = fen_to_int(Request::post('big_agency_money',0,'floatval,abs'));

        $updated_at = date('Y-m-d H:i:s');
        foreach ($datas as $name=>$val){

            $data = [];
            $data['content'] = $val;
            $data['updated_at'] = $updated_at;

            ConfigModel::where('name','=',$name)->update($data);
        }

        clear_db_config_cache();
        return Responses::data(200,'success');
    }


    public function withdraw_view(){

        $configs = [];

        //提现费用
        $configs['withdraw_min_money'] = fen_to_float(dbConfig('withdraw.min_money',0));
        $configs['withdraw_max_money'] = fen_to_float(dbConfig('withdraw.max_money',0));

        return view($this->directory .'/withdraw',compact('configs'));
    }


    public function withdraw(){

        $datas = [];

        //表单验证
        $validate = new SettingWithdrawValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        //代理
        $datas['withdraw.min_money'] = fen_to_int(Request::post('withdraw_min_money',0,'floatval,abs'));
        $datas['withdraw.max_money'] = fen_to_int(Request::post('withdraw_max_money',0,'floatval,abs'));

        $updated_at = date('Y-m-d H:i:s');
        foreach ($datas as $name=>$val){

            $data = [];
            $data['content'] = $val;
            $data['updated_at'] = $updated_at;

            ConfigModel::where('name','=',$name)->update($data);
        }

        clear_db_config_cache();
        return Responses::data(200,'success');
    }


    public function ranking_view(){

        $configs = [];


        //排行榜奖励
        $configs['ranking_tasks'] = json_decode(dbConfig('ranking.tasks','[]'),true);
        if(count($configs['ranking_tasks']) > 0){
            foreach ($configs['ranking_tasks'] as $key=>$val){
                $configs['ranking_tasks'][$key] = fen_to_float($val);
            }
        }

        $configs['ranking_tasks'] = json_encode($configs['ranking_tasks']);

        $configs['ranking_spreads'] = json_decode(dbConfig('ranking.spreads','[]'),true);
        if(count($configs['ranking_spreads']) > 0){
            foreach ($configs['ranking_spreads'] as $key=>$val){
                $configs['ranking_spreads'][$key] = fen_to_float($val);
            }
        }

        $configs['ranking_spreads'] = json_encode($configs['ranking_spreads']);

        return view($this->directory .'/ranking',compact('configs'));
    }


    public function ranking(){

        $datas = [];

        //表单验证
        $validate = new SettingRankingValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        //任务榜奖励
        $ranking_tasks = Request::post('ranking_tasks',[]);
        $datas['ranking.tasks'] = [];
        foreach ($ranking_tasks as $value){
            $datas['ranking.tasks'][] = fen_to_int(floatval($value));
        }

        $datas['ranking.tasks'] = json_encode($datas['ranking.tasks']);

        //推广榜奖励
        $ranking_spreads = Request::post('ranking_spreads',[]);
        $datas['ranking.spreads'] = [];
        foreach ($ranking_spreads as $value){
            $datas['ranking.spreads'][] = fen_to_int(floatval($value));
        }

        $datas['ranking.spreads'] = json_encode($datas['ranking.spreads']);

        $updated_at = date('Y-m-d H:i:s');
        foreach ($datas as $name=>$val){

            $data = [];
            $data['content'] = $val;
            $data['updated_at'] = $updated_at;

            ConfigModel::where('name','=',$name)->update($data);
        }

        clear_db_config_cache();
        return Responses::data(200,'success');
    }


    public function novice_reward_view(){

        return view($this->directory .'/novice_reward');

    }


    public function novice_reward_update(){

        //表单验证
        $validate = new SettingNoviceRewardValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $datas = Request::post('datas',[]);
        $ids = [];
        $updated_at = date('Y-m-d H:i:s');
        foreach ($datas as $index=>$item){
            $data = [];
            $data['title'] = $item['title'];
            $data['number'] = intval($item['number']);
            $data['award'] = fen_to_int($item['award']);
            $data['sort'] = 1000 - $index;
            $data['status'] = 1;

            if(isset($item['id']) && $item['id'] >= 1){
                $data['updated_at'] = $updated_at;
                NoviceRewardModel::where('id','=',$item['id'])->update($data);
                $ids[] = $item['id'];
            }else{
                $novice_reward = NoviceRewardModel::create($data);
                $ids[] = $novice_reward['id'];
            }
        }

        NoviceRewardModel::whereNotIn('id',$ids)->delete();
        return Responses::data(200,'success');
    }


    public function novice_reward_list(){

        $list = NoviceRewardModel::all();
        $datas = [];
        if ($list){
            foreach ($list as $data){
                $data['award'] = fen_to_float($data['award']);
                $datas[] = $data;
            }
        }

        return Responses::data(200,'success',$datas);
    }


    public function commission_view(){

        $configs = [];

        //活动分佣
        $configs['commission_task_rate'] = fen_to_float(dbConfig('commission.task_rate',0));
        $configs['commission_task_vip_rate'] = fen_to_float(dbConfig('commission.task_vip_rate',0));

        //任务分佣
        $configs['commission_spread_rate'] = fen_to_float(dbConfig('commission.spread_rate',0));
        $configs['commission_spread2_rate'] = fen_to_float(dbConfig('commission.spread2_rate',0));

        //好友分佣
        $configs['commission_buy_merchant_level_rate'] = fen_to_float(dbConfig('commission.buy_merchant_level_rate',0));
        $configs['commission_buy_refresh_rate'] = fen_to_float(dbConfig('commission.buy_refresh_rate',0));
        $configs['commission_activity_rec_rate'] = fen_to_float(dbConfig('commission.activity_rec_rate',0));

        return view($this->directory .'/commission',compact('configs'));
    }


    public function commission(){

        $datas = [];

        //表单验证
        $validate = new SettingCommissionValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        //活动分佣
        $datas['commission.task_rate'] = fen_to_int(Request::post('commission_task_rate',0,'floatval,abs'));
        $datas['commission.task_vip_rate'] = fen_to_int(Request::post('commission_task_vip_rate',0,'floatval,abs'));

        //活动分佣
        $datas['commission.spread_rate'] = fen_to_int(Request::post('commission_spread_rate',0,'floatval,abs'));
        $datas['commission.spread2_rate'] = fen_to_int(Request::post('commission_spread2_rate',0,'floatval,abs'));

        //好友分佣
        $datas['commission.buy_merchant_level_rate'] = fen_to_int(Request::post('commission_buy_merchant_level_rate',0,'floatval,abs'));
        $datas['commission.buy_refresh_rate'] = fen_to_int(Request::post('commission_buy_refresh_rate',0,'floatval,abs'));
        $datas['commission.activity_rec_rate'] = fen_to_int(Request::post('commission_activity_rec_rate',0,'floatval,abs'));

        $updated_at = date('Y-m-d H:i:s');
        foreach ($datas as $name=>$val){

            $data = [];
            $data['content'] = $val;
            $data['updated_at'] = $updated_at;

            ConfigModel::where('name','=',$name)->update($data);
        }

        clear_db_config_cache();
        return Responses::data(200,'success');
    }



    public function money_view(){

        $configs = [];

        //押金设置
        $configs['deposit_min_money'] = fen_to_float(dbConfig('deposit.min_money',0));
        $configs['deposit_recharge_min_money'] = fen_to_float(dbConfig('deposit.recharge_min_money',0));
        $configs['deposit_recharge_max_money'] = fen_to_float(dbConfig('deposit.recharge_max_money',0));

        //钱包充值
        $configs['recharge_min_money'] = fen_to_float(dbConfig('recharge.min_money',0));
        $configs['recharge_max_money'] = fen_to_float(dbConfig('recharge.max_money',0));

        return view($this->directory .'/money',compact('configs'));
    }


    public function money(){

        $datas = [];

        //表单验证
        $validate = new SettingMoneyValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        //押金设置
        $datas['deposit.min_money'] = fen_to_int(Request::post('deposit_min_money',0,'floatval,abs'));
        $datas['deposit.recharge_min_money'] = fen_to_int(Request::post('deposit_recharge_min_money',0,'floatval,abs'));
        $datas['deposit.recharge_max_money'] = fen_to_int(Request::post('deposit_recharge_max_money',0,'floatval,abs'));

        //钱包充值
        $datas['recharge.min_money'] = fen_to_int(Request::post('recharge_min_money',0,'floatval,abs'));
        $datas['recharge.max_money'] = fen_to_int(Request::post('recharge_max_money',0,'floatval,abs'));

        $updated_at = date('Y-m-d H:i:s');
        foreach ($datas as $name=>$val){

            $data = [];
            $data['content'] = $val;
            $data['updated_at'] = $updated_at;

            ConfigModel::where('name','=',$name)->update($data);
        }

        clear_db_config_cache();
        return Responses::data(200,'success');
    }



    public function friend_award_view(){

        $configs = [];

        $configs['friend_award_task_finish_1'] = fen_to_float(dbConfig('friend_award.task_finish_1',0));
        $configs['friend_award_withdraw_1'] = fen_to_float(dbConfig('friend_award.withdraw_1',0));
        $configs['friend_award_withdraw_2'] = fen_to_float(dbConfig('friend_award.withdraw_2',0));
        $configs['friend_award_withdraw_3'] = fen_to_float(dbConfig('friend_award.withdraw_3',0));
        $configs['friend_award_withdraw_4'] = fen_to_float(dbConfig('friend_award.withdraw_4',0));
        $configs['friend_award_withdraw_5'] = fen_to_float(dbConfig('friend_award.withdraw_5',0));
        $configs['friend_award_withdraw_6'] = fen_to_float(dbConfig('friend_award.withdraw_6',0));

        return view($this->directory .'/friend_award',compact('configs'));
    }


    public function friend_award(){

        $datas = [];

        //表单验证
        $validate = new SettingFriendAwardValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        //押金设置
        $datas['friend_award.task_finish_1'] = fen_to_int(Request::post('friend_award_task_finish_1',0,'floatval,abs'));
        $datas['friend_award.withdraw_1'] = fen_to_int(Request::post('friend_award_withdraw_1',0,'floatval,abs'));
        $datas['friend_award.withdraw_2'] = fen_to_int(Request::post('friend_award_withdraw_2',0,'floatval,abs'));
        $datas['friend_award.withdraw_3'] = fen_to_int(Request::post('friend_award_withdraw_3',0,'floatval,abs'));
        $datas['friend_award.withdraw_4'] = fen_to_int(Request::post('friend_award_withdraw_4',0,'floatval,abs'));
        $datas['friend_award.withdraw_5'] = fen_to_int(Request::post('friend_award_withdraw_5',0,'floatval,abs'));
        $datas['friend_award.withdraw_6'] = fen_to_int(Request::post('friend_award_withdraw_6',0,'floatval,abs'));
        $datas['friend_award.total'] = $datas['friend_award.task_finish_1']
            + $datas['friend_award.withdraw_1'] + $datas['friend_award.withdraw_2']
            + $datas['friend_award.withdraw_3'] + $datas['friend_award.withdraw_4']
            + $datas['friend_award.withdraw_5'] + $datas['friend_award.withdraw_6'];

        $updated_at = date('Y-m-d H:i:s');
        foreach ($datas as $name=>$val){

            $data = [];
            $data['content'] = $val;
            $data['updated_at'] = $updated_at;

            ConfigModel::where('name','=',$name)->update($data);
        }

        clear_db_config_cache();
        return Responses::data(200,'success');
    }


    public function refresh_number_view(){

        return view($this->directory .'/refresh_number');

    }


    public function refresh_number_update(){

        //表单验证
        $validate = new SettingRefreshNumberValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $datas = Request::post('datas',[]);
        $ids = [];
        $updated_at = date('Y-m-d H:i:s');
        foreach ($datas as $index=>$item){
            $data = [];
            $data['title'] = $item['title'];
            $data['number'] = intval($item['number']);
            $data['original_price'] = fen_to_int($item['original_price']);
            $data['price'] = fen_to_int($item['price']);
            $data['sort'] = 1000 - $index;
            $data['status'] = 1;

            if(isset($item['id']) && $item['id'] >= 1){
                $data['updated_at'] = $updated_at;
                RefreshSkuModel::where('id','=',$item['id'])->update($data);
                $ids[] = $item['id'];
            }else{
                $refresh_sku = RefreshSkuModel::create($data);
                $ids[] = $refresh_sku['id'];
            }
        }

        RefreshSkuModel::whereNotIn('id',$ids)->delete();
        return Responses::data(200,'success');
    }


    public function refresh_number_list(){

        $list = RefreshSkuModel::all();
        $datas = [];
        if ($list){
            foreach ($list as $data){
                $data['original_price'] = fen_to_float($data['original_price']);
                $data['price'] = fen_to_float($data['price']);
                $datas[] = $data;
            }
        }

        return Responses::data(200,'success',$datas);
    }



}
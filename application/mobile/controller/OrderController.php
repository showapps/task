<?php
/**
 * OrderController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/10/13
 */

namespace app\mobile\controller;


use app\common\exception\RequestException;
use app\common\model\OrderModel;
use app\common\model\UserWechatModel;
use app\mobile\traits\AuthTrait;
use app\mobile\validate\OrdersValidate;
use think\Db;
use think\Exception;
use think\facade\Request;
use Tools\Responses;
use Yansongda\Pay\Pay;

class OrderController
{

    use AuthTrait;

    public function __construct()
    {
        $this->initAuthInfo();
    }


    /**
     * 订单支付
     * */
    public function payment()
    {

        $validate = new OrdersValidate();

        if (!$validate->scene(Request::action())->check(Request::post())) {
            throw new Exception($validate->getError(),40004);
        }

        $id = Request::post('id');
        $order = OrderModel::where('user_id','=',self::$user_id)
            ->where('id','=',$id)
            ->find();

        if(!$order){
            throw new Exception('订单不存在',40401);
        }

        if($order['status'] != 1){
            throw new Exception('订单不是待支付状态',40401);
        }


        if(self::$user['is_wechat'] != 1){
            throw new Exception('请先绑定微信账号',40004);
        }

        //初始化配置
        $config = dbConfig('wechat_payment',[]);
        if((!$config) || (!isset($config['key'])) || (!$config['key'])){
            throw new RequestException( '未开启微信支付',40401);
        }

        $config['app_id'] = $config['mp_app_id'];

        $config['cert_client'] = env('root_path').'data/wxpay/cert/cert_client.pem';
        $config['cert_key'] = env('root_path').'data/wxpay/cert/cert_key.pem';
        $config['log'] = [
            'file'=>'logs/wechat.log'
        ];

        if(!$config){
            throw new Exception('申请失败:创建支付失败，未开启支付',50001);
        }

        $config['notify_url'] = url('/api/mobile/notify/wechat/payment',[],'json',true);
        //$config['cert_client'] = env('root_path').$config['cert_client'];
        //$config['cert_key'] = env('root_path').$config['cert_key'];
        $config['log']['file'] = env('runtime_path').'wechat.log';

        $mp_open_id = UserWechatModel::where('user_id','=',self::$user_id)->value('mp_open_id');

        $order = [
            'out_trade_no' => $order['order_no'],
            'body' => '购买商品',
            //'total_fee'      => $order['money'],
            'total_fee'      => 1,
            'openid' => $mp_open_id,
            'time_start'    => date('YmdHis',time()-1),//开始时间
            'time_expire'    => date('YmdHis',time() + (60*10)),//结束时间
        ];

        try{
            $apiResult = Pay::wechat($config)->mp($order);
        }catch (\Exception $e){
            throw new Exception('申请失败:创建支付失败，请重试'.$e->getMessage(),50001);
        }

        $data = [
            'app_id'=>$apiResult['appId'],
            'time_stamp'=>$apiResult['timeStamp'],
            'nonce_str'=>$apiResult['nonceStr'],
            'package'=>$apiResult['package'],
            'sign_type'=>$apiResult['signType'],
            'pay_sign'=>$apiResult['paySign'],
        ];

        return Responses::data(200,'success',$data);

    }

}
<?php
/**
 * NotifyController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/10/13
 */

namespace app\mobile\controller;


use app\common\model\ActivityAddToModel;
use app\common\model\ActivityModel;
use app\common\model\ActivityRecModel;
use app\common\model\DepositModel;
use app\common\model\OrderModel;
use app\common\model\UserModel;
use app\common\model\WalletModel;
use Carbon\Carbon;
use think\Db;
use think\Exception;
use think\facade\Hook;
use Yansongda\Pay\Pay;

class NotifyController
{



    /** 微信支付回调
     *
     **/
    public function wechatPayment(){

        $data = file_get_contents('php://input');
        file_put_contents(env('runtime_path').'wxpay_log_'.date('Y-m-d_H-i-s').mt_rand(111,999).'.log',$data);




        $config = dbConfig('wechat_payment',[]);
        $config['app_id'] = $config['mp_app_id'];

        $config['cert_client'] = env('root_path').'data/wxpay/cert/cert_client.pem';
        $config['cert_key'] = env('root_path').'data/wxpay/cert/cert_key.pem';
        $config['log'] = [
            'file'=>'logs/wechat.log'
        ];

        if(!$config){
            exit('申请失败:创建支付失败，未开启支付');
        }

        $pay = Pay::wechat($config);

        Db::startTrans();
        try{

            $data = $pay->verify();
            $order_no = $data['out_trade_no'];
            $trade_no = $data['transaction_id'];
            $payment_money = $data['total_fee'];
            $payment_dt = date('Y-m-d H:i:s');
            $order_info = OrderModel::where('order_no',$order_no)->find();

            if(!$order_info){
                //订单不存在
                return $pay->success();
            }

            if($order_info['status'] != 1){
                //订单已处理
                //return '订单已处理';
                return $pay->success();
            }

            //已支付
            if($data['result_code'] == 'SUCCESS'){

                //完成订单
                $order_id = $order_info['id'];
                OrderModel::where('id','=',$order_id)->update([
                    'payment_type'=>2,//微信支付
                    'payment_money'=>$payment_money,
                    'trade_no'=>$trade_no,
                    'status'=>100,
                    'finish_dt'=>$payment_dt,
                    'updated_at'=>$payment_dt,
                ]);

                //dump($order_info);
                //处理订单
                switch($order_info['product_type']){
                    case 11://发布活动

                        ActivityModel::where('id','=',$order_info['product_id'])
                            ->where('status','=',1)
                            ->update([
                                'status'=>2,
                                'updated_at'=>$payment_dt,
                            ]);

                        break;
                    case 12://刷新包

                        $update_data = [];
                        $update_data['updated_at'] = $payment_dt;
                        $update_data['refresh_number'] = Db::raw('refresh_number+'.$order_info['busines_id']);

                        UserModel::where('id','=',$order_info['user_id'])
                            ->update($update_data);

                        //购买刷新完成
                        Hook::listen('buy_refresh_finish',$order_info);

                        break;
                    case 13://活动加量

                        $addToInfo = ActivityAddToModel::where('id','=',$order_info['product_spec_id'])->find();
                        $activityInfo = ActivityModel::where('id','=',$order_info['product_id'])->find();
                        ActivityAddToModel::where('id','=',$order_info['product_spec_id'])
                            ->update([
                                'status'=>100,
                                'updated_at'=>$payment_dt,
                            ]);

                        $activity_update = [];
                        $activity_update['updated_at'] = $payment_dt;
                        $activity_update['total'] = Db::raw('total+'.$addToInfo['number']);
                        //已结束
                        if($activityInfo['status'] == 4){
                            $activity_update['status'] = 100;
                        }
                        ActivityModel::where('id','=',$order_info['product_id'])
                            ->update($activity_update);

                        break;
                    case 14://上推荐

                        $activityInfo = ActivityModel::where('id','=',$order_info['product_id'])->find();
                        $recInfo = ActivityRecModel::where('id','=',$order_info['product_spec_id'])->find();
                        ActivityRecModel::where('id','=',$order_info['product_spec_id'])
                            ->update([
                                'status'=>100,
                                'updated_at'=>$payment_dt,
                            ]);

                        $rec_end_dt = NULL;
                        if($activityInfo['rec_end_dt'] && strtotime($activityInfo['rec_end_dt']) > time()){
                            $rec_end_dt = Carbon::parse($activityInfo['rec_end_dt'])->addHours($recInfo['hour'])->toDateTimeString();
                        }else{
                            $rec_end_dt = Carbon::now()->addHours($recInfo['hour'])->toDateTimeString();
                        }

                        ActivityModel::where('id','=',$order_info['product_id'])
                            ->update([
                                'rec_end_dt'=>$rec_end_dt,
                                'updated_at'=>$payment_dt,
                            ]);

                        //活动上推荐完成
                        Hook::listen('activity_rec_finish',['order'=>$order_info,'rec'=>$recInfo]);

                        break;
                    case 21://微信充值

                        //写入支付
                        $wallet = WalletModel::create([
                            'type'=>1,
                            'trade_no'=>get_trade_no(),
                            'user_id'=>$order_info['user_id'],
                            'category'=>1002,//余额支付
                            'money'=>$order_info['money'],
                            'actual_amount'=>$order_info['money'],
                            'status'=>100,
                            'describe'=>'微信充值',
                            'finish_dt'=>$payment_dt,
                        ]);

                        //dump($wallet);
                        if(!$wallet){
                            throw new Exception('充值失败',50001);
                        }

                        //重新统计余额
                        UserModel::resetTotalAccount($order_info['user_id']);

                        break;
                    case 22://开通会员

                        $update_data = [];
                        $update_data['updated_at'] = $payment_dt;
                        $userInfo = UserModel::where('id','=',$order_info['user_id'])->find();

                        //超级商家
                        if($order_info['product_id'] == 1){
                            $update_data['merchant_level'] = 1;
                            if($userInfo['merchant_level'] == 1){
                                $update_data['merchant_level_edate'] = Carbon::parse($userInfo['merchant_level_edate'])->addMonths($order_info['busines_id'])->toDateString();
                            }else{
                                $update_data['merchant_level_edate'] = Carbon::now()->addMonths($order_info['busines_id'])->toDateString();;
                            }
                        }

                        //送刷新
                        if($order_info['busines_child_id'] > 0){
                            $update_data['refresh_number'] = Db::raw('refresh_number+'.$order_info['busines_child_id']);
                        }

                        UserModel::where('id','=',$order_info['user_id'])
                            ->update($update_data);

                        //开通超级会员
                        if($order_info['product_id'] == 1){
                            //购买刷新完成
                            Hook::listen('buy_merchant_level_finish',$order_info);
                        }

                        break;
                    case 23://押金充值

                        //写入支付
                        $deposit = DepositModel::create([
                            'type'=>1,
                            'trade_no'=>get_trade_no(),
                            'user_id'=>$order_info['user_id'],
                            'category'=>1002,//用户充值
                            'money'=>$order_info['money'],
                            'actual_amount'=>$order_info['money'],
                            'status'=>100,
                            'describe'=>'押金充值',
                            'finish_dt'=>$payment_dt,
                        ]);

                        //dump($deposit);
                        if(!$deposit){
                            throw new Exception('充值失败',50001);
                        }

                        //重新统计押金
                        UserModel::resetTotalDeposit($order_info['user_id']);

                        break;
                }

            }

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();
            //dump($e->getMessage());

        }

        return $pay->success();
    }

}
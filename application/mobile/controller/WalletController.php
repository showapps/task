<?php


namespace app\mobile\controller;


use app\common\exception\DbException;
use app\common\exception\RequestException;
use app\common\model\ActivityAddToModel;
use app\common\model\ActivityModel;
use app\common\model\ActivityRecModel;
use app\common\model\DepositModel;
use app\common\model\OrderModel;
use app\common\model\UserModel;
use app\common\model\UserWechatModel;
use app\common\model\WalletModel;
use app\common\model\WalletWithdrawModel;
use app\mobile\traits\AuthTrait;
use app\mobile\validate\WalletPaymentValidate;
use app\mobile\validate\WalletRechargeValidate;
use app\mobile\validate\WalletValidate;
use Carbon\Carbon;
use think\Db;
use think\Exception;
use think\facade\Hook;
use think\facade\Request;
use Tools\Responses;

class WalletController
{

    use AuthTrait;

    public function __construct()
    {
        $this->initAuthInfo();
    }


    /**
     * 数据列表
     *
     * @return Responses
     * */
    public function lists()
    {

        $WalletModel = new WalletModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $WalletModel = $WalletModel->where('id','in',$value);
                        }
                        break;
                    case 'types':
                        if($value && is_array($value)){
                            $WalletModel = $WalletModel->where('type','in',$value);
                        }
                        break;
                    case 'categorys':
                        if($value && is_array($value)){
                            $WalletModel = $WalletModel->where('category','in',$value);
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $WalletModel = $WalletModel->where('status','in',$value);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page.size',10));

        $lists = $WalletModel
            ->where('user_id','=',self::$user_id)
            ->order('id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = [];
        if($lists['data']){
            foreach ($lists['data'] as $data){

                //分转元
                $data['money'] = fen_to_float($data['money']);
                $data['actual_amount'] = fen_to_float($data['actual_amount']);

                $datas[] = $data;
            }
        }

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


    /**
     * 数据详情
     *
     * @return Responses
     * */
    public function detail()
    {

        //表单验证
        $validate = new WalletValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = WalletModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '数据不存在',40401);
        }

        //分转元
        $info['money'] = fen_to_float($info['money']);
        $info['actual_amount'] = fen_to_float($info['actual_amount']);

        if($info['type'] == 2){
            $info['withdraw'] = WalletWithdrawModel::where('wallet_id','=',$info['id'])->find();
            //分转元
            $info['withdraw']['money'] = fen_to_float($info['withdraw']['money']);
            $info['withdraw']['actual_amount'] = fen_to_float($info['withdraw']['actual_amount']);
            $info['withdraw']['service_charge'] = fen_to_float($info['withdraw']['service_charge']);
            //毫转元
            $info['withdraw']['service_charge_rate'] = hao_to_float($info['withdraw']['service_charge_rate']);
        }

        return Responses::data(200, 'success',$info);

    }




    /**
     * 余额提现
     *
     * @return Responses
     * */
    public function withdraw()
    {

        //表单验证
        $validate = new WalletValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        if(self::$user['certification_status'] != 100){
            throw new DbException('请先通过实名认证',40003);
        }

        //禁止提现
        if(in_black(self::$user_id,1003)){
            throw new RequestException('平台禁止您提现，请联系客服解冻',40003);
        }

        $money = Request::post('money',0,'floatval,abs');
        $describe = '用户提现';

        $config = [
            'min_money'=>dbConfig('withdraw.min_money',10),
            'max_money'=>dbConfig('withdraw.max_money',1000)
        ];

        if($money < fen_to_float($config['min_money'])){
            throw new DbException('最小提现金额不能小于'.fen_to_float($config['min_money']).'元',40003);
        }else if($money > fen_to_float($config['max_money'])){
            throw new DbException('最大提现金额不能大于'.fen_to_float($config['max_money']).'元',40003);
        }

        //手续费率
        $service_charge_rate = dbConfig('withdraw.sc_rate',0);
        //超级商人
        if(self::$user['merchant_level'] == 1){
            $service_charge_rate = fen_to_float(dbConfig('withdraw.merchant_sc_rate',0));
        }else if(self::$user['user_level'] == 1){
            $service_charge_rate = fen_to_float(dbConfig('withdraw.user_sc_rate',0));
        }

        $service_charge = fen_to_int($money * ($service_charge_rate / 100));

        $money = fen_to_int($money);
        $withdraw_data = [];
        $withdraw_data['account_type'] = Request::post('account_type',0,'intval');
        $withdraw_data['user_id'] = self::$user_id;
        $withdraw_data['status'] = 1;
        $withdraw_data['money'] = $money;
        $withdraw_data['describe'] = $describe;
        $withdraw_data['service_charge_rate'] = $service_charge_rate;
        $withdraw_data['service_charge'] = $service_charge;
        $withdraw_data['actual_amount'] = $withdraw_data['money'] - $withdraw_data['service_charge'];


        //支付宝
        if($withdraw_data['account_type'] == 1){
            $bank_id = Request::post('bank_id',0,'intval');
            $bank = UserBankModel::where('id','=',$bank_id)
                ->where('user_id','=',self::$user_id)
                ->find();
            if(!$bank){
                throw new DbException('到账银行卡不存在',40003);
            }

            //支付宝信息
            $withdraw_data['alipay_account'] = $bank['alipay_account'];
            $withdraw_data['alipay_name'] = $bank['alipay_name'];

        //微信提现到余额
        }else{
            if(self::$user['is_wechat'] != 1){
                throw new DbException('请先绑定微信账号',40003);
            }

            //微信信息
            $withdraw_data['wechat_open_id'] = UserWechatModel::where('user_id','=',self::$user_id)->value('miniapp_open_id');

        }

        //加锁提现
        $lock_file = env('root_path').'locks/user_account_'.self::$user_id.'_lock.txt';
        if(!file_exists($lock_file)){
            file_put_contents($lock_file,'');
        }

        $handle = fopen($lock_file, 'w');
        //锁定
        if(flock($handle,LOCK_EX)){

            try{

                //获取余额
                $balance = UserModel::getBalanceTotal(self::$user_id);
                if($balance < $money){
                    throw new Exception('提现失败：账户余额不足！',40003);
                }

                $trade_no = get_trade_no();
                //写入扣款
                $wallet = WalletModel::create([
                    'type'=>2,
                    'trade_no'=>$trade_no,
                    'user_id'=>self::$user_id,
                    'category'=>2002,
                    'money'=>$money,
                    'actual_amount'=>$withdraw_data['actual_amount'],
                    'status'=>1,
                    'describe'=>$describe,
                ]);

                if(!$wallet){
                    throw new Exception('扣款失败',50001);
                }

                //写入提现记录
                $withdraw_data['wallet_id'] = $wallet['id'];
                $withdraw_data['trade_no'] = $trade_no;
                $withdraw = WalletWithdrawModel::create($withdraw_data);
                if(!$withdraw_data){
                    throw new Exception('扣款失败',50001);
                }

                //重新统计余额
                UserModel::resetTotalAccount(self::$user_id);

                //解锁
                flock($handle, LOCK_UN);
                //关闭文件
                fclose($handle);

                return Responses::data(200, 'success',['id'=>$wallet['id']]);
            }catch (\Exception $e){
                //解锁
                flock($handle, LOCK_UN);
                //关闭文件
                fclose($handle);
                return Responses::data(50001, $e->getMessage(),[UserModel::getLastSql()]);
            }

        }

    }




    /**
     * 余额支付
     *
     * @return Responses
     * */
    public function payment()
    {

        //表单验证
        $validate = new WalletPaymentValidate();
        $vResult = $validate->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $order_id = Request::post('order_id',0,'intval,abs');
        $order_info = OrderModel::where('id','=',$order_id)
            ->where('user_id','=',self::$user_id)
            ->find();

        if(!$order_info){
            throw new DbException('订单不存在',40003);
        }
        if($order_info['status'] == 2){
            throw new DbException('订单已取消',40003);
        }

        if(in_array($order_info['status'],[101,102])){
            throw new DbException('订单已退款',40003);
        }

        if($order_info['status'] == 100){
            throw new DbException('订单已支付',40003);
        }

        if($order_info['status'] != 1){
            throw new DbException('只能支付待支付的订单',40003);
        }

        //余额支付用于充值余额
        if($order_info['product_type'] == 21){
            throw new DbException('不能使用余额来支付充值的订单',40003);
        }

        $money = $order_info['money'];
        if(self::$user['balance'] < $money){
            throw new DbException('支付失败：账户余额不足！',40003);
        }


        //加锁支付
        $lock_file = env('root_path').'locks/user_account_'.self::$user_id.'_lock.txt';

        $file = fopen($lock_file,"w+");

        //锁定
        if(flock($file,LOCK_EX)){

            Db::startTrans();
            $trade_no = get_trade_no();
            try{

                //获取余额
                $balance = UserModel::getBalanceTotal(self::$user_id);
                if($balance < $money){
                    throw new Exception('支付失败：账户余额不足！',40003);
                }

                $payment_time = time();
                $payment_dt = date('Y-m-d H:i:s',$payment_time);

                $describe_type_list = [
                    11=>'发布任务支出',
                    12=>'购买刷新卡',
                    13=>'活动加量支出',
                    14=>'活动上推荐支出',
                    22=>'开通超级商人',
                    23=>'充值保证金',
                ];

                //写入支付
                $wallet = WalletModel::create([
                    'type'=>2,
                    'trade_no'=>$trade_no,
                    'user_id'=>self::$user_id,
                    'category'=>2003,//余额支付
                    'money'=>$money,
                    'actual_amount'=>$money,
                    'status'=>100,
                    'describe'=>$describe_type_list[$order_info['product_type']],
                    'finish_dt'=>$payment_dt,
                ]);

                if(!$wallet){
                    throw new Exception('扣款失败',50001);
                }

                //重新统计余额
                UserModel::resetTotalAccount(self::$user_id);


                //完成订单
                OrderModel::where('id','=',$order_id)->update([
                    'payment_type'=>1,//余额支付
                    'payment_money'=>$money,
                    'trade_no'=>$trade_no,
                    'status'=>100,
                    'finish_dt'=>$payment_dt,
                    'updated_at'=>$payment_dt,
                ]);

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
                               $update_data['merchant_level_edate'] = Carbon::now()->addMonths($order_info['busines_id'])->toDateString();
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

                Db::commit();
                //解锁
                flock($file,LOCK_UN);
                //关闭文件
                fclose($file);

                return Responses::data(200, 'success',['id'=>$wallet['id']]);
            }catch (\Exception $e){
                Db::rollback();
                //解锁
                flock($file,LOCK_UN);
                //关闭文件
                fclose($file);
                return Responses::data(50001, $e->getMessage());
            }

        }

    }




    /**
     * 余额充值
     *
     * @return Responses
     * */
    public function recharge()
    {

        //表单验证
        $validate = new WalletRechargeValidate();
        $vResult = $validate->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $order = [];
        $order['order_no'] = get_rand_order_no();
        $order['user_id'] = self::$user_id;
        $order['product_type'] = 21;
        $order['product_title'] = '微信充值';
        $order['product_id'] = 0;
        $order['money'] = fen_to_int(Request::post('money',0,'floatval,abs'));
        $order['status'] = 1;
        $order['end_dt'] = date('Y-m-d H:i:s',time() + 1800);

        //父级
        $order['parent_id'] = self::$user['parent_id'];
        $order['parent2_id'] = self::$user['parent2_id'];

        //判断最小、最大充值金额
        $recharge_min_money = dbConfig('recharge.min_money',0);
        if($order['money'] < $recharge_min_money){
            throw new DbException('最小充值金额必须大于'.fen_to_float($recharge_min_money).'元',40003);
        }
        $recharge_max_money = dbConfig('recharge.max_money',0);
        if($order['money'] > $recharge_max_money){
            throw new DbException('最大充值金额必须小于'.fen_to_float($recharge_max_money).'元',40003);
        }

        Db::startTrans();
        try{

            $order = OrderModel::create($order);
            if(!$order){
                throw new DbException('充值失败:写入订单失败',50001);
            }

            Db::commit();
            return Responses::data(200, 'success',[
                'order'=>[
                    'id'=>$order['id'],
                    'money'=>fen_to_float($order['money'])
                ]
            ]);

        }catch (\app\common\exception\Exception $e){

            Db::rollback();
            return Responses::data(50002, '加量失败');

        }

    }


    /**
     * 数据列表
     *
     * @return Responses
     * */
    public function accountList()
    {

        $datas = [];
        $datas['wechat'] = UserWechatModel::where('user_id',self::$user_id)->find();
        return Responses::data(200, 'success',$datas);

    }


    /**
     * 数据详情
     *
     * @return Responses
     * */
    public function accountDetail()
    {

        //表单验证
        $validate = new WalletValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = WalletModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '数据不存在',40401);
        }

        //分转元
        $info['money'] = fen_to_float($info['money']);
        $info['actual_amount'] = fen_to_float($info['actual_amount']);

        if($info['type'] == 2){
            $info['withdraw'] = WalletWithdrawModel::where('wallet_id','=',$info['id'])->find();
            //分转元
            $info['withdraw']['money'] = fen_to_float($info['withdraw']['money']);
            $info['withdraw']['actual_amount'] = fen_to_float($info['withdraw']['actual_amount']);
            $info['withdraw']['service_charge'] = fen_to_float($info['withdraw']['service_charge']);
            //毫转元
            $info['withdraw']['service_charge_rate'] = hao_to_float($info['withdraw']['service_charge_rate']);
        }

        return Responses::data(200, 'success',$info);

    }


}
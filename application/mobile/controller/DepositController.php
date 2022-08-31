<?php


namespace app\mobile\controller;


use app\common\exception\DbException;
use app\common\exception\RequestException;
use app\common\model\DepositModel;
use app\common\model\OrderModel;
use app\common\model\UserModel;
use app\common\model\WalletModel;
use app\mobile\traits\AuthTrait;
use app\mobile\validate\DepositRechargeValidate;
use app\mobile\validate\DepositValidate;
use think\Db;
use think\Exception;
use think\facade\Request;
use Tools\Responses;

class DepositController
{

    use AuthTrait;

    public function __construct()
    {
        $this->initAuthInfo();
    }




    /**
     * 押金提现
     *
     * @return Responses
     * */
    public function withdraw()
    {

        //表单验证
        $validate = new DepositValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        if(self::$user['certification_status'] != 100){
            throw new DbException('请先通过实名认证',40003);
        }

        $money = Request::post('money',0,'floatval,abs');
        $money_fen = fen_to_int($money);
        $describe = '押金提现到余额';

        //不满足最小押金
        $min_money = dbConfig('deposit.min_money',0);
        if(self::$user['deposit'] < $min_money){
            throw new DbException('必须满足最小押金'.fen_to_float($min_money).'元才能提现',40003);
        }

        //不是全部提现
        if(self::$user['deposit'] != $money_fen){
            if((self::$user['deposit'] - $money_fen) < $min_money){
                throw new DbException('如不是全部提现，剩余押金必须满足'.fen_to_float($min_money).'元才能提现',40003);
            }
        }

        $money = $money_fen;

        //加锁提现
        $lock_file = env('root_path').'locks/user_deposit_'.self::$user_id.'_lock.txt';
        if(!file_exists($lock_file)){
            file_put_contents($lock_file,'');
        }

        $handle = fopen($lock_file, 'w');
        //锁定
        if(flock($handle,LOCK_EX)){

            try{

                //获取押金
                $deposit = UserModel::getDepositTotal(self::$user_id);
                if($deposit < $money){
                    throw new Exception('提现失败：账户押金不足！',40003);
                }

                //不满足最小押金
                $min_money = dbConfig('deposit.min_money',0);
                if($deposit < $min_money){
                    throw new DbException('必须满足最小押金'.fen_to_float($min_money).'元才能提现',40003);
                }

                //不是全部提现
                if($deposit != $money_fen){
                    if(($deposit - $money_fen) < $min_money){
                        throw new DbException('如不是全部提现，剩余押金必须满足'.fen_to_float($min_money).'元才能提现',40003);
                    }
                }

                $trade_no = get_trade_no();
                //写入扣款
                $deposit = DepositModel::create([
                    'type'=>2,
                    'trade_no'=>$trade_no,
                    'user_id'=>self::$user_id,
                    'category'=>2002,
                    'money'=>$money,
                    'actual_amount'=>$money,
                    'status'=>1,
                    'describe'=>$describe,
                ]);

                if(!$deposit){
                    throw new Exception('扣款失败',50001);
                }

                //重新统计押金
                UserModel::resetTotalDeposit(self::$user_id);

                //解锁
                flock($handle, LOCK_UN);
                //关闭文件
                fclose($handle);

                return Responses::data(200, 'success',['id'=>$deposit['id']]);
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
     * 押金充值
     *
     * @return Responses
     * */
    public function recharge()
    {

        //表单验证
        $validate = new DepositRechargeValidate();
        $vResult = $validate->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $order = [];
        $order['order_no'] = get_rand_order_no();
        $order['user_id'] = self::$user_id;
        $order['product_type'] = 23;
        $order['product_title'] = '押金充值';
        $order['product_id'] = 0;
        $order['money'] = fen_to_int(Request::post('money',0,'floatval,abs'));
        $order['status'] = 1;
        $order['end_dt'] = date('Y-m-d H:i:s',time() + 1800);

        //父级
        $order['parent_id'] = self::$user['parent_id'];
        $order['parent2_id'] = self::$user['parent2_id'];

        //判断最小、最大充值金额
        $recharge_min_money = dbConfig('deposit.recharge_min_money',0);
        if($order['money'] < $recharge_min_money){
            throw new DbException('最小充值金额必须大于'.fen_to_float($recharge_min_money).'元',40003);
        }
        $recharge_max_money = dbConfig('deposit.recharge_max_money',0);
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
            return Responses::data(50002, '充值失败');

        }

    }

}
<?php

namespace app\common\behavior;

use app\common\model\ActivityModel;
use app\common\model\NoviceRewardListModel;
use app\common\model\NoviceRewardModel;
use app\common\model\TaskModel;
use app\common\model\UserModel;
use app\common\model\WalletModel;
use think\facade\Request;

class WithdrawFinishBehavior
{

    public function run(Request $request, $user)
    {

        //已完成的提现数
        $withdraw_total = WalletModel::where('type','=',2)
            ->where('category','=',2002)
            ->where('status','=',100)
            ->count();

        //有父级
        if($user['parent_id'] >= 1){

            $nick_name = $user['nick_name'];

            $friend_award_withdraw_1 = dbConfig('friend_award.withdraw_1',0);
            if($withdraw_total == 1 && $friend_award_withdraw_1 > 0)
            {
                //新增到余额
                $wallet = WalletModel::create([
                    'type'=>1,
                    'trade_no'=>get_trade_no(),
                    'user_id'=>$user['parent_id'],
                    'category'=>1004,//邀请奖励
                    'money'=>$friend_award_withdraw_1,
                    'actual_amount'=>$friend_award_withdraw_1,
                    'busines_id'=>2,
                    'busines_child_id'=>1,
                    'status'=>100,
                    'describe'=>'['.$nick_name.']首次完成提现奖励',
                    'finish_dt'=>date('Y-m-d H:i:s',time()),
                ]);
                //重新统计余额
                UserModel::resetTotalAccount($user['parent_id']);
            }

            //第2次
            $friend_award_withdraw_2 = dbConfig('friend_award.withdraw_2',0);
            if($withdraw_total == 2 && $friend_award_withdraw_2 > 0)
            {
                //新增到余额
                $wallet = WalletModel::create([
                    'type'=>1,
                    'trade_no'=>get_trade_no(),
                    'user_id'=>$user['parent_id'],
                    'category'=>1004,//邀请奖励
                    'money'=>$friend_award_withdraw_2,
                    'actual_amount'=>$friend_award_withdraw_2,
                    'busines_id'=>2,
                    'busines_child_id'=>2,
                    'status'=>100,
                    'describe'=>'['.$nick_name.']第2次完成提现奖励',
                    'finish_dt'=>date('Y-m-d H:i:s',time()),
                ]);
                //重新统计余额
                UserModel::resetTotalAccount($user['parent_id']);
            }

            //第3次
            $friend_award_withdraw_3 = dbConfig('friend_award.withdraw_3',0);
            if($withdraw_total == 2 && $friend_award_withdraw_3 > 0)
            {
                //新增到余额
                $wallet = WalletModel::create([
                    'type'=>1,
                    'trade_no'=>get_trade_no(),
                    'user_id'=>$user['parent_id'],
                    'category'=>1004,//邀请奖励
                    'money'=>$friend_award_withdraw_3,
                    'actual_amount'=>$friend_award_withdraw_3,
                    'busines_id'=>2,
                    'busines_child_id'=>3,
                    'status'=>100,
                    'describe'=>'['.$nick_name.']第3次完成提现奖励',
                    'finish_dt'=>date('Y-m-d H:i:s',time()),
                ]);
                //重新统计余额
                UserModel::resetTotalAccount($user['parent_id']);
            }

            //第4次
            $friend_award_withdraw_4 = dbConfig('friend_award.withdraw_4',0);
            if($withdraw_total == 2 && $friend_award_withdraw_4 > 0)
            {
                //新增到余额
                $wallet = WalletModel::create([
                    'type'=>1,
                    'trade_no'=>get_trade_no(),
                    'user_id'=>$user['parent_id'],
                    'category'=>1004,//邀请奖励
                    'money'=>$friend_award_withdraw_4,
                    'actual_amount'=>$friend_award_withdraw_4,
                    'busines_id'=>2,
                    'busines_child_id'=>4,
                    'status'=>100,
                    'describe'=>'['.$nick_name.']第4次完成提现奖励',
                    'finish_dt'=>date('Y-m-d H:i:s',time()),
                ]);
                //重新统计余额
                UserModel::resetTotalAccount($user['parent_id']);
            }

            //第5次
            $friend_award_withdraw_5 = dbConfig('friend_award.withdraw_5',0);
            if($withdraw_total == 2 && $friend_award_withdraw_5 > 0)
            {
                //新增到余额
                $wallet = WalletModel::create([
                    'type'=>1,
                    'trade_no'=>get_trade_no(),
                    'user_id'=>$user['parent_id'],
                    'category'=>1004,//邀请奖励
                    'money'=>$friend_award_withdraw_5,
                    'actual_amount'=>$friend_award_withdraw_5,
                    'busines_id'=>2,
                    'busines_child_id'=>5,
                    'status'=>100,
                    'describe'=>'['.$nick_name.']第5次完成提现奖励',
                    'finish_dt'=>date('Y-m-d H:i:s',time()),
                ]);
                //重新统计余额
                UserModel::resetTotalAccount($user['parent_id']);
            }

            //第6次
            $friend_award_withdraw_6 = dbConfig('friend_award.withdraw_6',0);
            if($withdraw_total == 2 && $friend_award_withdraw_6 > 0)
            {
                //新增到余额
                $wallet = WalletModel::create([
                    'type'=>1,
                    'trade_no'=>get_trade_no(),
                    'user_id'=>$user['parent_id'],
                    'category'=>1004,//邀请奖励
                    'money'=>$friend_award_withdraw_6,
                    'actual_amount'=>$friend_award_withdraw_6,
                    'busines_id'=>2,
                    'busines_child_id'=>6,
                    'status'=>100,
                    'describe'=>'['.$nick_name.']第6次完成提现奖励',
                    'finish_dt'=>date('Y-m-d H:i:s',time()),
                ]);
                //重新统计余额
                UserModel::resetTotalAccount($user['parent_id']);
            }
        }

    }

}
<?php

namespace app\common\behavior;


use app\common\model\UserModel;
use app\common\model\WalletModel;
use think\facade\Request;

class ActivityRecFinishBehavior
{

    public function run(Request $request, $params)
    {

        $order = $params['order'];
        $recInfo = $params['rec'];

        //计算父级收益
        $activity_rec_rate = dbConfig('commission.activity_rec_rate',0);
        if($order['parent_id'] >= 1){

            //实际费用
            $money = $recInfo['total_price'] - $recInfo['discount_total_price'];

            $parent_award = fen_to_int(fen_to_float($money) * (fen_to_float($activity_rec_rate) / 100));
            //最小 0.01 元
            $parent_award = $parent_award >= 1 ? $parent_award : 1;
            $nick_name = UserModel::where('id','=',$order['user_id'])->value('nick_name');
            //新增到余额
            $wallet = WalletModel::create([
                'type'=>1,
                'trade_no'=>get_trade_no(),
                'user_id'=>$order['parent_id'],
                'category'=>1007,//分佣奖励
                'money'=>$parent_award,
                'actual_amount'=>$parent_award,
                'busines_id'=>$order['id'],
                'busines_child_id'=>3,//活动上推荐
                'status'=>100,
                'describe'=>'好友 '.$nick_name.' 活动上推荐',
                'finish_dt'=>date('Y-m-d H:i:s',time()),
            ]);
            //重新统计余额
            UserModel::resetTotalAccount($order['parent_id']);
        }

    }

}
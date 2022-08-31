<?php

namespace app\common\behavior;


use app\common\model\UserModel;
use app\common\model\WalletModel;
use think\facade\Request;

class BuyRefreshFinishBehavior
{

    public function run(Request $request, $order)
    {

        //计算父级收益
        $buy_refresh_rate = dbConfig('commission.buy_refresh_rate',0);
        if($order['parent_id'] >= 1){
            $parent_award = fen_to_int(fen_to_float($order['money']) * (fen_to_float($buy_refresh_rate) / 100));
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
                'busines_child_id'=>2,//购买刷新次数
                'status'=>100,
                'describe'=>'好友 '.$nick_name.' 购买刷新次数',
                'finish_dt'=>date('Y-m-d H:i:s',time()),
            ]);
            //重新统计余额
            UserModel::resetTotalAccount($order['parent_id']);
        }

    }

}
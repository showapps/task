<?php
namespace app\mobile\controller;

use app\common\model\ConfigModel;
use Tools\Responses;

class SystemController
{


    public function config()
    {

        $datas = [];

        //全部配置
        $datas = ConfigModel::getMobile();
        $datas['site']['kefu_ids'] = json_decode($datas['site']['kefu_ids'],true);
        $datas['commission']['spread_rate'] = fen_to_float($datas['commission']['spread_rate']);
        $datas['commission']['spread2_rate'] = fen_to_float($datas['commission']['spread2_rate']);
        $datas['commission']['task_rate'] = fen_to_float($datas['commission']['task_rate']);
        $datas['commission']['task_vip_rate'] = fen_to_float($datas['commission']['task_vip_rate']);
        $datas['commission']['buy_merchant_level_rate'] = fen_to_float($datas['commission']['buy_merchant_level_rate']);
        $datas['commission']['buy_refresh_rate'] = fen_to_float($datas['commission']['buy_refresh_rate']);
        $datas['commission']['activity_rec_rate'] = fen_to_float($datas['commission']['activity_rec_rate']);
        $datas['activity']['rec_money'] = fen_to_float($datas['activity']['rec_money']);
        $datas['activity']['rec_sc_rate'] = fen_to_float($datas['activity']['rec_sc_rate']);
        $datas['activity']['rec_user_sc_rate'] = fen_to_float($datas['activity']['rec_user_sc_rate']);
        $datas['activity']['rec_merchant_sc_rate'] = fen_to_float($datas['activity']['rec_merchant_sc_rate']);
        $datas['withdraw']['min_money'] = fen_to_float($datas['withdraw']['min_money']);
        $datas['withdraw']['max_money'] = fen_to_float($datas['withdraw']['max_money']);
        $datas['withdraw']['sc_rate'] = fen_to_float($datas['withdraw']['sc_rate']);
        $datas['withdraw']['user_sc_rate'] = fen_to_float($datas['withdraw']['user_sc_rate']);
        $datas['withdraw']['merchant_sc_rate'] = fen_to_float($datas['withdraw']['merchant_sc_rate']);

        //充值
        $datas['deposit']['min_money'] = fen_to_float($datas['deposit']['min_money']);
        $datas['deposit']['recharge_min_money'] = fen_to_float($datas['deposit']['recharge_min_money']);
        $datas['deposit']['recharge_max_money'] = fen_to_float($datas['deposit']['recharge_max_money']);

        //充值
        $datas['recharge']['min_money'] = fen_to_float($datas['recharge']['min_money']);
        $datas['recharge']['max_money'] = fen_to_float($datas['recharge']['max_money']);

        //好友奖励
        $datas['friend_award']['task_finish_1'] = fen_to_float($datas['friend_award']['task_finish_1']);
        $datas['friend_award']['withdraw_1'] = fen_to_float($datas['friend_award']['withdraw_1']);
        $datas['friend_award']['withdraw_2'] = fen_to_float($datas['friend_award']['withdraw_2']);
        $datas['friend_award']['withdraw_3'] = fen_to_float($datas['friend_award']['withdraw_3']);
        $datas['friend_award']['withdraw_4'] = fen_to_float($datas['friend_award']['withdraw_4']);
        $datas['friend_award']['withdraw_5'] = fen_to_float($datas['friend_award']['withdraw_5']);
        $datas['friend_award']['withdraw_6'] = fen_to_float($datas['friend_award']['withdraw_6']);

        return Responses::data(200,'success',$datas);

    }


    public function detail()
    {

        $data = [];
        $data['date'] = [];

        //年信息
        $data['date']['cur_year'] = date('Y');
        $data['date']['next_year'] = date('Y',strtotime("last day of +1 Year"));
        $data['date']['last_year'] = date('Y',strtotime("first day of -1 Year"));

        //月信息
        $data['date']['cur_month'] = date('Ym');
        $data['date']['next_month'] = date('Ym',strtotime("last day of +1 Month"));
        $data['date']['last_month'] = date('Ym',strtotime("first day of -1 Month"));

        //日信息
        $data['date']['cur_day'] = date('Ymd');
        $data['date']['next_day'] = date('Ymd',strtotime("last day of +1 Day"));
        $data['date']['last_day'] = date('Ymd',strtotime("first day of -1 Day"));

        return Responses::data(200,'success',$data);

    }

}

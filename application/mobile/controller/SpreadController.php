<?php
/**
 * SpreadController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/10/12
 */

namespace app\mobile\controller;


use app\common\model\AdModel;
use app\common\model\UserModel;
use app\common\model\WalletModel;
use app\mobile\traits\AuthTrait;
use Tools\Responses;
use function GuzzleHttp\Psr7\str;

class SpreadController
{
    use AuthTrait;


    public function notice(){

        //查找最近100个会员
        $list = UserModel::where('parent_id','<>',0)
            ->field('parent_id,count(1) as total')
            ->group('parent_id')
            ->order('id DESC')
            ->limit(10)
            ->select();

        $friend_award_total = fen_to_float(dbConfig('friend_award.total',0));
        $datas = [];
        if($list){
            foreach ($list as $data){

                $nick_name = UserModel::where('id','=',$data['parent_id'])->value('nick_name');
                $content = '“'.$nick_name.'”刚刚邀请了'.$data['total'].'个好友，预计获得奖励+'.($data['total'] * $friend_award_total).'元';

                $datas[] = $content;
            }
        }

        return Responses::data(200,'success',$datas);
    }



    public function total(){

        $this->initAuthInfo();

        $datas = [
            'today_invitation'=>UserModel::where('parent_id','=',self::$user_id)
                ->whereTime('created_at','>',date('Y-m-d H:i:s',strtotime(date('Y-m-d')) - 1))
                ->count(),
            'total_invitation'=>UserModel::where('parent_id','=',self::$user_id)->count(),
            'today_income'=>fen_to_float(WalletModel::where('user_id','=',self::$user_id)
                ->where('type','=',1)
                ->whereTime('finish_dt','>',date('Y-m-d 00:00:00'))
                ->sum('actual_amount')),
            'total_income'=>fen_to_float(self::$user['income']),
        ];

        return Responses::data(200,'success',$datas);
    }



    public function poster(){

        $this->initAuthInfo();

        $background_info = AdModel::where('position','=','spread-poster-background')->find();

        if(!$background_info){
            return Responses::data(40003,'生成失败：未设置海报背景');
        }

        $datas = [
            'backgroundImage'=>$background_info['content']['resource'],
            'qrcode'=>create_poster_qrcode(self::$user['invitation_code']),
        ];

        return Responses::data(200,'success',$datas);
    }

}
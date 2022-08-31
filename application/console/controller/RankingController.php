<?php

namespace app\console\controller;


use app\common\model\RankingListModel;
use app\common\model\TaskModel;
use app\common\model\UserModel;
use Tools\Responses;

class RankingController
{



    public function task(){

        set_time_limit(0);

        //当前月份
        $cur_month = date('Ym');
        if(date('d') == '01'){
            $cur_month = date('Ym',strtotime("first day of -1 Month"));
        }

        //总奖金
        $tasks = json_decode(dbConfig('ranking.tasks','[]'),true);
        if(count($tasks) < 1){
            return false;
        }

        //查找本月完成前N个
        $datas = TaskModel::where('status','=',100)
            ->where('date_format(finish_dt,\'%Y%m\') = '.$cur_month)
            ->field('user_id,count(1) as total')
            ->group('user_id')
            ->order('total DESC')
            ->limit(count($tasks))
            ->select();

        if($datas){

            $installs = [];
            //佣金计算
            foreach ($datas as $key=>$row){
                //数据组装
                $data = [];
                $data['type'] = 1;
                $data['user_id'] = $row['user_id'];
                $data['month'] = $cur_month;
                $data['index'] = $key + 1;
                $data['achieve'] = $row['total'];
                $data['reward'] = $tasks[$key];
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');

                $installs[] = $data;
            }

            RankingListModel::where('month','=',$cur_month)
                ->where('type','=',1)
                ->delete();

            RankingListModel::insertAll($installs);

        }

        return Responses::data(200,'success');
    }



    public function spread(){

        set_time_limit(0);
        //当前月份
        $cur_month = date('Ym');
        if(date('d') == '01'){
            $cur_month = date('Ym',strtotime("first day of -1 Month"));
        }

        //总奖金
        $spreads = json_decode(dbConfig('ranking.spreads','[]'),true);
        if(count($spreads) < 1){
            return false;
        }

        //查找本月注册用户前N个
        $datas = UserModel::where('parent_id','<>',0)
            ->where('date_format(created_at,\'%Y%m\') = '.$cur_month)
            ->field('parent_id,count(1) as total')
            ->group('parent_id')
            ->order('total DESC')
            ->limit(count($spreads))
            ->select();

        if($datas){

            $installs = [];
            //佣金计算
            foreach ($datas as $key=>$row){

                //数据组装
                $data = [];
                $data['type'] = 2;
                $data['user_id'] = $row['parent_id'];
                $data['month'] = $cur_month;
                $data['index'] = $key + 1;
                $data['achieve'] = $row['total'];
                $data['reward'] = $spreads[$key];
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');

                $installs[] = $data;
            }

            RankingListModel::where('month','=',$cur_month)
                ->where('type','=',2)
                ->delete();

            RankingListModel::insertAll($installs);

        }

        return Responses::data(200,'success');
    }

}
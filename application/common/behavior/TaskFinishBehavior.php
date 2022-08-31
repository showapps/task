<?php

namespace app\common\behavior;


use app\common\model\ActivityModel;
use app\common\model\NoviceRewardListModel;
use app\common\model\NoviceRewardModel;
use app\common\model\TaskModel;
use app\common\model\UserModel;
use app\common\model\WalletModel;
use Carbon\Carbon;
use think\facade\Request;

class TaskFinishBehavior
{

    public function run(Request $request, $task)
    {

        //计算任务完成数
        $novice_reward_list = NoviceRewardModel::where('status','=',1)
            ->order('sort DESC')
            ->select();

        //已完成的任务数
        $finish_total = TaskModel::where('user_id','=',$task['user_id'])
            ->where('status','=',100)
            ->count();

        //新手任务
        if($novice_reward_list){

            //循环处理
            foreach ($novice_reward_list as $novice_reward_row){

                //未领取
                $is_finish = NoviceRewardListModel::where('user_id','=',$task['user_id'])
                    ->where('award_id','=',$novice_reward_row['id'])
                    ->count();

                //未领取 && 达成条件
                if($is_finish < 1 && $novice_reward_row['number'] <= $finish_total){
                    //新增奖励
                    NoviceRewardListModel::create([
                        'user_id'=>$task['user_id'],
                        'award_id'=>$novice_reward_row['id'],
                        'number'=>$novice_reward_row['number'],
                        'award'=>$novice_reward_row['award'],
                    ]);

                    //新增到余额
                    $wallet = WalletModel::create([
                        'type'=>1,
                        'trade_no'=>get_trade_no(),
                        'user_id'=>$task['user_id'],
                        'category'=>1006,//活动奖励
                        'money'=>$novice_reward_row['award'],
                        'actual_amount'=>$novice_reward_row['award'],
                        'busines_id'=>$novice_reward_row['id'],
                        'busines_child_id'=>$novice_reward_row['number'],
                        'status'=>100,
                        'describe'=>'完成新手奖励 ['.$novice_reward_row['title'].']',
                        'finish_dt'=>date('Y-m-d H:i:s',time()),
                    ]);

                }
            }

        }


        $user = UserModel::where('id','=',$task['user_id'])->find();
        $nick_name = $user['nick_name'];
        $activity_title = ActivityModel::where('id','=',$task['activity_id'])->value('title');
        //计算父级收益
        $spread_rate = dbConfig('commission.spread_rate',0);
        $spread2_rate = dbConfig('commission.spread2_rate',0);
        if($task['parent_user_id'] >= 1 && $spread_rate > 0){
            $parent_award = fen_to_int(fen_to_float($task['money']) * ($spread_rate / 100 / 100));
            //最小 0.01 元
            $parent_award = $parent_award >= 1 ? $parent_award : 1;
            //新增到余额
            $wallet = WalletModel::create([
                'type'=>1,
                'trade_no'=>get_trade_no(),
                'user_id'=>$task['parent_user_id'],
                'category'=>1007,//分佣奖励
                'money'=>$parent_award,
                'actual_amount'=>$parent_award,
                'busines_id'=>$task['id'],
                'busines_child_id'=>4,
                'status'=>100,
                'describe'=>'['.$activity_title.']邀请佣金',
                'finish_dt'=>date('Y-m-d H:i:s',time()),
            ]);
            //
            //重新统计余额
            UserModel::resetTotalAccount($task['parent_user_id']);

            //父父级
            if($task['parent2_user_id'] >= 1 && $spread2_rate > 0){
                $parent2_award = fen_to_int(fen_to_float($task['money']) * ($spread2_rate / 100 / 100));
                //最小 0.01 元
                $parent2_award = $parent2_award >= 1 ? $parent2_award : 1;
                //新增到余额
                $wallet = WalletModel::create([
                    'type'=>1,
                    'trade_no'=>get_trade_no(),
                    'user_id'=>$task['parent2_user_id'],
                    'category'=>1007,//分佣奖励
                    'money'=>$parent2_award,
                    'actual_amount'=>$parent2_award,
                    'busines_id'=>$task['id'],
                    'busines_child_id'=>5,
                    'status'=>100,
                    'describe'=>'['.$activity_title.']邀请佣金',
                    'finish_dt'=>date('Y-m-d H:i:s',time()),
                ]);
                //重新统计余额
                UserModel::resetTotalAccount($task['parent2_user_id']);
            }
        }

        //首次完成任务
        $friend_award_task_finish_1 = dbConfig('friend_award.task_finish_1',0);
        if($task['parent_user_id'] && $finish_total == 1 && $friend_award_task_finish_1 > 0)
        {
            //新增到余额
            $wallet = WalletModel::create([
                'type'=>1,
                'trade_no'=>get_trade_no(),
                'user_id'=>$task['parent2_user_id'],
                'category'=>1004,//邀请奖励
                'money'=>$friend_award_task_finish_1,
                'actual_amount'=>$friend_award_task_finish_1,
                'busines_id'=>1,
                'busines_child_id'=>1,
                'status'=>100,
                'describe'=>'['.$nick_name.']首次完成任务奖励',
                'finish_dt'=>date('Y-m-d H:i:s',time()),
            ]);
            //重新统计余额
            UserModel::resetTotalAccount($task['parent2_user_id']);
        }

        //喵达人升级门槛
        $user_vip_threshold = dbConfig('vip.user_vip_threshold',0);
        if($user['user_level'] != 1 && $user_vip_threshold > 0 && $finish_total >= $user_vip_threshold){
            UserModel::where('id','=',$user['id'])->update([
                'user_level'=>1,
                'user_level_edate'=>Carbon::now()->addYears(2)->toDateString(),
            ]);
        }

        //重新统计余额
        UserModel::resetTotalAccount($task['user_id']);

    }

}
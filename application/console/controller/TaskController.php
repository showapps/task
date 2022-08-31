<?php

namespace app\console\controller;


use app\common\model\ActivityModel;
use app\common\model\TaskModel;
use app\common\model\UserModel;
use app\common\model\WalletModel;
use think\Db;
use think\facade\Hook;
use Tools\Responses;

class TaskController
{



    public function submit_end(){

        set_time_limit(0);

        //列表
        $list = TaskModel::where('status','=',1)
            ->where('date_format(submit_end_dt,\'%Y%m%d%H%i%s\') < '.date('YmdHis'))
            ->order('submit_end_dt ASC')
            ->limit(10)
            ->select();

        if($list){
            foreach ($list as $data){
                $result = TaskModel::where('id','=',$data['id'])
                    ->where('status','=',1)
                    ->update([
                        'status'=>4,
                        'reason'=>'过期未提交',
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ]);
                if($result){
                    ActivityModel::where('id','=',$data['activity_id'])->setInc('cancel_total',1);
                }
            }
        }

        return Responses::data(200,'success');
    }



    public function audit_end(){

        set_time_limit(0);

        //列表
        $list = TaskModel::where('status','=',2)
            ->where('date_format(audit_end_dt,\'%Y%m%d%H%i%s\') < '.date('YmdHis'))
            ->order('audit_end_dt ASC')
            ->limit(10)
            ->select();

        if($list){
            foreach ($list as $task_info){

                $task_id = $task_info['id'];
                $activity_id = $task_info['activity_id'];
                //加锁扣除
                $lock_file = env('root_path').'locks/task_pass_'.$task_id.'_lock.txt';

                $file = fopen($lock_file,"w+");

                //锁定
                if(flock($file,LOCK_EX)){

                    $update_data = [];
                    $update_data['status'] = 100;//审核驳回
                    $update_data['audit_dt'] = date('Y-m-d H:i:s');
                    $update_data['finish_dt'] = date('Y-m-d H:i:s');
                    $update_data['updated_at'] = date('Y-m-d H:i:s');

                    $user = UserModel::where('id','=',$task_info['user_id'])->find();
                    $activity_info = ActivityModel::where('id','=',$activity_id)->find();

                    //计算佣金
                    $commission_money = get_task_commission_calc($activity_info['price'],$user);
                    $update_data['commission'] = $commission_money;

                    Db::startTrans();
                    try{

                        //修改任务
                        $result = TaskModel::where('id','=',$task_id)
                            ->where('status','=',2)
                            ->update($update_data);

                        if($result){

                            //修改活动
                            $result = ActivityModel::where('id','=',$activity_id)->setInc('finish_total',1);

                            //写入余额
                            $wallet = WalletModel::create([
                                'type'=>1,
                                'trade_no'=>get_trade_no(),
                                'user_id'=>$user['id'],
                                'category'=>1003,//任务佣金
                                'money'=>$commission_money,
                                'actual_amount'=>$commission_money,
                                'busines_id'=>$activity_id,
                                'busines_child_id'=>$task_id,
                                'status'=>100,
                                'describe'=>'完成['.$activity_info['title'].']',
                                'finish_dt'=>date('Y-m-d H:i:s',time()),
                            ]);

                            //任务完成
                            Hook::listen('task_finish',$task_info);

                            //重新统计余额
                            UserModel::resetTotalAccount($user['id']);
                        }

                        Db::commit();
                        //解锁
                        flock($file,LOCK_UN);
                        //关闭文件
                        fclose($file);

                    }catch (\Exception $e){
                        Db::rollback();
                        //解锁
                        flock($file,LOCK_UN);
                        //关闭文件
                        fclose($file);
                    }

                }
            }
        }

        return Responses::data(200,'success');
    }



    public function recheck_submit_end(){

        set_time_limit(0);

        //列表
        $list = TaskModel::where('status','=',3)
            ->where('date_format(recheck_submit_end_dt,\'%Y%m%d%H%i%s\') < '.date('YmdHis'))
            ->order('recheck_submit_end_dt ASC')
            ->limit(10)
            ->select();

        if($list){
            foreach ($list as $data){
                $result = TaskModel::where('id','=',$data['id'])
                    ->where('status','=',3)
                    ->update([
                        'status'=>4,
                        'reason'=>'过期未提出复审',
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ]);
                if($result){
                    ActivityModel::where('id','=',$data['activity_id'])->setInc('cancel_total',1);
                }
            }
        }

        return Responses::data(200,'success');
    }



    public function recheck_audit_end(){

        set_time_limit(0);

        //列表
        $list = TaskModel::where('status','=',5)
            ->where('recheck_status','=',2)
            ->where('date_format(recheck_audit_end_dt,\'%Y%m%d%H%i%s\') < '.date('YmdHis'))
            ->order('audit_end_dt ASC')
            ->limit(10)
            ->select();

        if($list){
            foreach ($list as $task_info){

                $task_id = $task_info['id'];
                $activity_id = $task_info['activity_id'];
                //加锁扣除
                $lock_file = env('root_path').'locks/task_pass_'.$task_id.'_lock.txt';

                $file = fopen($lock_file,"w+");

                //锁定
                if(flock($file,LOCK_EX)){

                    $update_data = [];
                    $update_data['status'] = 100;//审核驳回
                    $update_data['audit_dt'] = date('Y-m-d H:i:s');
                    $update_data['finish_dt'] = date('Y-m-d H:i:s');
                    $update_data['updated_at'] = date('Y-m-d H:i:s');

                    $user = UserModel::where('id','=',$task_info['user_id'])->find();
                    $activity_info = ActivityModel::where('id','=',$activity_id)->find();

                    //计算佣金
                    $commission_money = get_task_commission_calc($activity_info['price'],$user);
                    $update_data['commission'] = $commission_money;

                    Db::startTrans();
                    try{

                        //修改任务
                        $result = TaskModel::where('id','=',$task_id)
                            ->where('status','=',5)
                            ->where('recheck_status','=',2)
                            ->update($update_data);

                        if($result){

                            //修改活动
                            $result = ActivityModel::where('id','=',$activity_id)->setInc('finish_total',1);

                            //写入余额
                            $wallet = WalletModel::create([
                                'type'=>1,
                                'trade_no'=>get_trade_no(),
                                'user_id'=>$user['id'],
                                'category'=>1003,//任务佣金
                                'money'=>$commission_money,
                                'actual_amount'=>$commission_money,
                                'busines_id'=>$activity_id,
                                'busines_child_id'=>$task_id,
                                'status'=>100,
                                'describe'=>'完成['.$activity_info['title'].']',
                                'finish_dt'=>date('Y-m-d H:i:s',time()),
                            ]);

                            //任务完成
                            Hook::listen('task_finish',$task_info);

                            //重新统计余额
                            UserModel::resetTotalAccount($user['id']);
                        }

                        Db::commit();
                        //解锁
                        flock($file,LOCK_UN);
                        //关闭文件
                        fclose($file);

                    }catch (\Exception $e){
                        Db::rollback();
                        //解锁
                        flock($file,LOCK_UN);
                        //关闭文件
                        fclose($file);
                    }

                }
            }
        }

        return Responses::data(200,'success');
    }



    public function report_open_end(){

        set_time_limit(0);

        //列表
        $list = TaskModel::where('status','=',5)
            ->where('recheck_status','=',3)
            ->where('date_format(recheck_submit_end_dt,\'%Y%m%d%H%i%s\') < '.date('YmdHis'))
            ->order('recheck_submit_end_dt ASC')
            ->limit(10)
            ->select();

        if($list){
            foreach ($list as $data){
                $result = TaskModel::where('id','=',$data['id'])
                    ->where('status','=',5)
                    ->where('recheck_status','=',3)
                    ->update([
                        'status'=>4,
                        'reason'=>'过期未提出举报',
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ]);
                if($result){
                    ActivityModel::where('id','=',$data['activity_id'])->setInc('cancel_total',1);
                }
            }
        }

        return Responses::data(200,'success');
    }



    public function report_bl_end(){

        set_time_limit(0);

        //列表
        $list = TaskModel::where('status','=',6)
            ->where('report_status','=',1)
            ->where('date_format(report_bl_end_dt,\'%Y%m%d%H%i%s\') < '.date('YmdHis'))
            ->order('report_bl_end_dt ASC')
            ->limit(10)
            ->select();

        if($list){
            foreach ($list as $data){


                //商家
                if($data['report_identity'] == 1){

                    $task_info = $data;
                    $task_id = $data['id'];
                    $activity_id = $task_info['activity_id'];
                    //加锁扣除
                    $lock_file = env('root_path').'locks/task_pass_'.$task_id.'_lock.txt';

                    $file = fopen($lock_file,"w+");

                    //锁定
                    if(flock($file,LOCK_EX)){

                        $update_data = [];
                        $update_data['status'] = 100;//审核驳回
                        $update_data['report_status'] = 102;//审核驳回
                        $update_data['audit_dt'] = date('Y-m-d H:i:s');
                        $update_data['finish_dt'] = date('Y-m-d H:i:s');
                        $update_data['updated_at'] = date('Y-m-d H:i:s');

                        $user = UserModel::where('id','=',$task_info['user_id'])->find();
                        $activity_info = ActivityModel::where('id','=',$activity_id)->find();

                        //计算佣金
                        $commission_money = get_task_commission_calc($activity_info['price'],$user);
                        $update_data['commission'] = $commission_money;

                        Db::startTrans();
                        try{

                            //修改任务
                            $result = TaskModel::where('id','=',$task_id)
                                ->where('status','=',6)
                                ->where('report_status','=',1)
                                ->update($update_data);

                            if($result){

                                //修改活动
                                $result = ActivityModel::where('id','=',$activity_id)->setInc('finish_total',1);

                                //写入余额
                                $wallet = WalletModel::create([
                                    'type'=>1,
                                    'trade_no'=>get_trade_no(),
                                    'user_id'=>$user['id'],
                                    'category'=>1003,//任务佣金
                                    'money'=>$commission_money,
                                    'actual_amount'=>$commission_money,
                                    'busines_id'=>$activity_id,
                                    'busines_child_id'=>$task_id,
                                    'status'=>100,
                                    'describe'=>'完成['.$activity_info['title'].']',
                                    'finish_dt'=>date('Y-m-d H:i:s',time()),
                                ]);

                                //任务完成
                                Hook::listen('task_finish',$task_info);

                                //重新统计余额
                                UserModel::resetTotalAccount($user['id']);
                            }

                            Db::commit();
                            //解锁
                            flock($file,LOCK_UN);
                            //关闭文件
                            fclose($file);

                        }catch (\Exception $e){
                            dump($e->getMessage());
                            Db::rollback();
                            //解锁
                            flock($file,LOCK_UN);
                            //关闭文件
                            fclose($file);
                        }

                    }

                }else{
                    $result = TaskModel::where('id','=',$data['id'])
                        ->where('status','=',6)
                        ->where('report_status','=',1)
                        ->update([
                            'status'=>4,
                            'report_status'=>102,
                            'report_reason'=>json_encode(['text'=>'过期未提交辩论']),
                            'updated_at'=>date('Y-m-d H:i:s'),
                        ]);
                    if($result){
                        ActivityModel::where('id','=',$data['activity_id'])->setInc('cancel_total',1);
                    }
                }
            }
        }

        return Responses::data(200,'success');
    }

}
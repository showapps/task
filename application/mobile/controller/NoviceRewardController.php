<?php

namespace app\mobile\controller;


use app\common\model\ActivityModel;
use app\common\model\NoviceRewardListModel;
use app\common\model\NoviceRewardModel;
use app\common\model\TaskModel;
use app\mobile\traits\AuthTrait;
use app\mobile\traits\ModuleTrait;
use think\facade\Request;
use Tools\Auth;
use Tools\Responses;

class NoviceRewardController
{

    use AuthTrait;
    use ModuleTrait;


    /**
     * 奖励列表
     *
     * @return Responses
     * */
    public function lists()
    {

        $user_id = 0;
        $token = Request::post('token','');
        if($token){
            if(Auth::guard(self::$module_name)->token($token)->check()){
                $user_id = Auth::guard(self::$module_name)->token($token)->id();
            }
        }

        $NoviceRewardModel = new NoviceRewardModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $NoviceRewardModel = $NoviceRewardModel->where('id','in',$value);
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $NoviceRewardModel = $NoviceRewardModel->where('status','in',$value);
                        }
                        break;
                }
            }
        }


        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $NoviceRewardModel
            ->order('sort DESC,id DESC')
            ->paginate($page_size)
            ->toArray();

        $includes = Request::post('includes',[]);
        $datas = [];
        $task_finish_count = 0;
        if($user_id){
            $task_finish_count = TaskModel::where('user_id','=',$user_id)
                ->where('status','=',100)
                ->count();
        }
        if($lists['data']){
            foreach ($lists['data'] as $data){

                $data['is_finish'] = false;
                $data['award'] = fen_to_float($data['award']);

                //用户已登录
                if($user_id){
                    $data['is_finish'] = (bool)NoviceRewardListModel::where('award_id','=',$data['id'])
                        ->where('user_id','=',$user_id)
                        ->count();
                }

                $datas[] = $data;
            }
        }

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages','task_finish_count','user_id'));

    }
}
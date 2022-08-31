<?php


namespace app\mobile\controller;


use app\common\exception\DbException;
use app\common\exception\Exception;
use app\common\exception\RequestException;
use app\common\model\ActivityAddToModel;
use app\common\model\ActivityCategoryModel;
use app\common\model\ActivityModel;
use app\common\model\ActivityRecModel;
use app\common\model\ActivityRefreshModel;
use app\common\model\ActivityStepModel;
use app\common\model\OrderModel;
use app\common\model\TaskModel;
use app\common\model\TaskStepModel;
use app\common\model\UserModel;
use app\common\model\WalletModel;
use app\mobile\traits\AuthTrait;
use app\mobile\validate\ActivityAddToValidate;
use app\mobile\validate\ActivitySaveValidate;
use app\mobile\validate\ActivityValidate;
use app\mobile\validate\TaskApplyValidate;
use app\mobile\validate\TaskAuditValidate;
use app\mobile\validate\TaskReportValidate;
use app\mobile\validate\TaskSubmitValidate;
use app\mobile\validate\TaslValidate;
use Carbon\Carbon;
use think\Db;
use think\facade\Hook;
use think\facade\Request;
use Tools\Responses;

class TaskController
{

    use AuthTrait;

    public function __construct(){
        $this->initAuthInfo();
    }



    /**
     * 任务列表
     *
     * @return Responses
     * */
    public function lists()
    {

        $TaskModel = new TaskModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $TaskModel = $TaskModel->where('id','in',$value);
                        }
                        break;
                    case 'category_id':
                        $value = (int)$value;
                        if($value){
                            $TaskModel = $TaskModel->where('category_id','=',$value);
                        }
                        break;
                    case 'user_id':
                        $value = (int)$value;
                        if($value){
                            $TaskModel = $TaskModel->where('user_id','=',$value);
                        }
                        break;
                    case 'merchant_id':
                        $value = (int)$value;
                        if($value){
                            $TaskModel = $TaskModel->where('merchant_id','=',$value);
                        }
                        break;
                    case 'user_or_merchant_id':
                        $value = (int)$value;
                        if($value){
                            $TaskModel = $TaskModel->where('(user_id = '.$value.' OR merchant_id = '.$value.' )');
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $TaskModel = $TaskModel->where('status','in',$value);
                        }
                        break;
                    case 'user_delete':
                        $value = (int)$value;
                        if($value){
                            $TaskModel = $TaskModel->where('user_delete','=',$value);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $TaskModel
            ->with([
                'activity'=>function($query){

                },
                'category'=>function($query){
                    $query->field('id,title');
                },
                'user'=>function($query){
                    $query->field('id,nick_name,true_name,avatar');
                },
                'merchant'=>function($query){
                    $query->field('id,nick_name,true_name,avatar');
                },
                'steps'=>function($query){
                    $query->field('id,activity_id,type,image,describe');
                },
                'task_steps'=>function($query){
                    $query->field('id,task_id,step_id,type,num_type,image,sub_image,describe');
                    $query->where('type','=',2);
                }
            ])
            ->order('id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = [];
        $includes = Request::post('includes',[]);
        if($lists['data']){

            //获取活动佣金比例
            $task_commission_rate = dbConfig('commission.task_rate',0);
            $task_vip_commission_rate = dbConfig('commission.task_vip_rate',0);

            foreach ($lists['data'] as $data){

                $data['activity']['price'] = fen_to_float($data['activity']['price']);

                //计算佣金
                $data['activity']['task_commission'] = get_task_commission($data['activity']['price'],$task_commission_rate);
                $data['activity']['task_vip_commission'] = get_task_commission($data['activity']['price'],$task_vip_commission_rate);

                //默认普通佣金
                $data['activity']['commission'] = $data['activity']['task_commission'];
                //用户是会员
                $levels = [self::$user['user_level'],self::$user['merchant_level']];
                if(in_array(1,$levels)){
                    $data['activity']['commission'] = $data['activity']['task_vip_commission'];
                }

                $data['activity']['commission'] = fen_to_float($data['activity']['commission']);

                //实收佣金
                $data['commission'] = fen_to_float($data['commission']);

                //用户倒计时
                if(in_array('user_end_date',$includes)){

                    //提交倒计时
                    if($data['status'] == 1){
                        $data['end_info'] = get_end_info($data['submit_end_dt']);
                    }

                    //复审倒计时
                    if($data['status'] == 3){
                        $data['end_info'] = get_end_info($data['recheck_submit_end_dt']);
                    }

                    //举报倒计时
                    if($data['status'] == 5 && $data['recheck_status'] == 3){
                        $data['end_info'] = get_end_info($data['report_open_end_dt']);
                    }

                    //辩论倒计时
                    if($data['status'] == 6 && $data['report_status'] == 1){
                        $data['end_info'] = get_end_info($data['report_bl_end_dt']);
                    }
                }

                $datas[] = $data;
            }
        }

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }



    /**
     * 任务列表
     *
     * @return Responses
     * */
    public function reportList()
    {

        $TaskModel = new TaskModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'type':

                        $user_id = self::$user_id;
                        //被举报的
                        if($value == 1){
                            $TaskModel = $TaskModel->where('status','=',6)
                                ->where('report_status','<>',0)
                                ->where('((report_identity = 1 AND user_id = '.$user_id.') OR (report_identity = 2 AND merchant_id = '.$user_id.'))');
                        //我举报的
                        }else{
                            $TaskModel = $TaskModel->where('status','=',6)
                                ->where('report_status','<>',0)
                                ->where('((report_identity = 1 AND merchant_id = '.$user_id.') OR (report_identity = 2 AND user_id = '.$user_id.'))');
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $TaskModel
            ->with([
                'activity'=>function($query){

                },
                'category'=>function($query){
                    $query->field('id,title');
                },
                'user'=>function($query){
                    $query->field('id,nick_name,true_name,avatar');
                },
                'merchant'=>function($query){
                    $query->field('id,nick_name,true_name,avatar');
                }
            ])
            ->order('id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = [];
        $includes = Request::post('includes',[]);
        if($lists['data']){
            foreach ($lists['data'] as $data){

                //用户倒计时
                if(in_array('user_end_date',$includes)){

                    //辩论倒计时
                    if($data['report_status'] == 1){
                        $data['end_info'] = get_end_info($data['report_bl_end_dt']);
                    }
                }

                $datas[] = $data;
            }
        }

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


    /**
     * 维权统计
     *
     * @return Responses
     * */
    public function reportTotal()
    {

        $user_id = self::$user_id;
        $datas = [];
        $datas[0] = TaskModel::where('status','=',6)
            ->where('report_status','=',1)
            ->where('((report_identity = 1 AND user_id = '.$user_id.') OR (report_identity = 2 AND merchant_id = '.$user_id.'))')
            ->count();

        $datas[1] = TaskModel::where('status','=',6)
            ->where('report_status','=',1)
            ->where('((report_identity = 1 AND merchant_id = '.$user_id.') OR (report_identity = 2 AND user_id = '.$user_id.'))')
            ->count();

        return Responses::data(200, 'success',$datas);

    }


    /**
     * 任务详情
     *
     * @return Responses
     * */
    public function detail()
    {

        //表单验证
        $validate = new TaslValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = TaskModel::where('id','=',$id)
            ->with([
                'category'=>function($query){
                    $query->field('id,title');
                },
                'user'=>function($query){
                    $query->field('id,nick_name,true_name,avatar');
                },
                'merchant'=>function($query){
                    $query->field('id,nick_name,true_name,avatar,certification_status,deposit,merchant_level');
                },
                'steps'=>function($query){
                    $query->field('id,activity_id,type,image,describe');
                },
                'task_steps'=>function($query){
                    $query->field('id,task_id,step_id,type,num_type,image,sub_image,describe');
                    $query->where('type','=',2);
                }
            ])
            ->find();
        if(!$info){
            throw new RequestException( '任务不存在',40402);
        }

        //活动价格
        $info['activity']['price'] = fen_to_float($info['activity']['price']);

        //计算佣金
        $info['activity']['task_commission'] = get_task_commission($info['activity']['price'],dbConfig('commission.task_rate',0));
        $info['activity']['task_vip_commission'] = get_task_commission($info['activity']['price'],dbConfig('commission.task_vip_rate',0));

        //默认普通佣金
        $info['activity']['commission'] = $info['activity']['task_commission'];
        //用户是会员
        $levels = [self::$user['user_level'],self::$user['merchant_level']];
        if(in_array(1,$levels)){
            $info['activity']['commission'] = $info['activity']['task_vip_commission'];
        }
        $info['activity']['commission'] = fen_to_float($info['activity']['commission']);



        //实收佣金
        $info['commission'] = fen_to_float($info['commission']);

        $includes = Request::post('includes',[]);
        //用户倒计时
        if(in_array('user_end_date',$includes)){

            //提交倒计时
            if($info['status'] == 1){
                $info['end_info'] = get_end_info($info['submit_end_dt']);
            }

            //复审倒计时
            if($info['status'] == 3){
                $info['end_info'] = get_end_info($info['recheck_submit_end_dt']);
            }

            //举报倒计时
            if($info['status'] == 5 && $info['recheck_status'] == 3){
                $info['end_info'] = get_end_info($info['report_open_end_dt']);
            }

            //辩论倒计时
            if($info['status'] == 6 && $info['report_status'] == 1){
                $info['end_info'] = get_end_info($info['report_bl_end_dt']);
            }
        }

        return Responses::data(200, 'success',$info);

    }


    /**
     * 商家统计
     *
     * @return Responses
     * */
    public function merchant_total()
    {

        $datas = [];
        $datas[1] = TaskModel::where('merchant_id','=',self::$user_id)
            ->where('status','=',2)
            ->count();
        $datas[4] = TaskModel::where('merchant_id','=',self::$user_id)
            ->where('((status = 5 AND recheck_status = 2) OR (status = 6 AND report_status = 1 AND report_identity = 2))')
            ->count();

        return Responses::data(200, 'success',$datas);

    }


    /**
     * 用户统计
     *
     * @return Responses
     * */
    public function user_total()
    {

        $datas = [];
        $datas[0] = TaskModel::where('user_id','=',self::$user_id)
            ->where('status','=',1)
            ->count();

        $datas[3] = TaskModel::where('user_id','=',self::$user_id)
            ->where('status','=',3)
            ->count();

        $datas[4] = TaskModel::where('user_id','=',self::$user_id)
            ->where('status','=',6)
            ->where('report_status','=',1)
            ->where('report_identity','=',1)
            ->count();

        return Responses::data(200, 'success',$datas);

    }


    /**
     * 任务申请
     *
     * @return Responses
     * */
    public function apply()
    {

        //未实名认证
        if(self::$user['certification_status'] != 100){
            throw new RequestException( '申请失败：请先通过实名认证',40003);
        }

        $datas = Request::post();

        //表单验证
        $validate = new TaskApplyValidate();
        $vResult = $validate->check($datas);
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        //禁止接任务
        if(in_black(self::$user_id,1005)){
            throw new RequestException('平台禁止您接任务，请联系客服解冻',40003);
        }

        $activity_id = Request::post('activity_id',0,'intval');
        $activity_info = ActivityModel::where('id','=',$activity_id)->find();
        if(!$activity_info){
            throw new DbException('申请失败：活动不存在',50001);
        }

        if($activity_info['status'] != 100){
            throw new DbException('申请失败：活动未上线',50001);
        }

        if($activity_info['merchant_id'] == self::$user_id){
            throw new DbException('申请失败：不可以申请自己发布的活动',50001);
        }

        $finishCount = TaskModel::where('activity_id','=',$activity_id)
            ->where('user_id','=',self::$user_id)
            ->count();

        if($finishCount > 0){
            throw new DbException('申请失败：不可以申请已完成的活动',50001);
        }

        $data = [];
        $data['activity_id'] = $activity_id;
        $data['category_id'] = $activity_info['category_id'];
        $data['user_id'] = self::$user_id;
        $data['merchant_id'] = $activity_info['merchant_id'];
        $data['money'] = $activity_info['price'];
        $data['parent_user_id'] = self::$user['parent_id'];
        $data['parent2_user_id'] = self::$user['parent2_id'];
        $data['vip_level'] = self::$user['user_level'];
        $data['status'] = 1;//待提交
        $data['end_dt'] = Carbon::now()->addHours($activity_info['limited_submit'])->toDateTimeString();//结束时间
        $data['submit_end_dt'] = Carbon::now()->addHours($activity_info['limited_submit'])->toDateTimeString();//结束时间

        //初始化审核信息
        $data['audit_reason'] = [];
        $data['recheck_reason'] = [];
        $data['report_data'] = [];
        $data['report_bl_data'] = [];
        $data['report_reason'] = [];

        //加锁申请
        $lock_file = env('root_path').'locks/activity_task_'.$activity_id.'_lock.txt';
        if(!file_exists($lock_file)){
            file_put_contents($lock_file,'');
        }

        $handle = fopen($lock_file, 'w');
        //锁定
        if(flock($handle,LOCK_EX)){

            //库存不足
            if($activity_info['apply_total'] - $activity_info['cancel_total'] >= $activity_info['total']){
                throw new Exception('申请失败：活动数量不足！',40003);
            }

            //已申请
            $applyCount = TaskModel::where('user_id','=',self::$user_id)
                ->where('activity_id','=',$activity_id)
                ->whereNotIn('status',[4,100])
                ->count();

            if($applyCount > 0){
                throw new Exception('申请失败：已申请该活动！',40003);
            }

            Db::startTrans();
            try{

                //写入任务
                $apply_res = TaskModel::create($data);

                if(!$apply_res){
                    throw new Exception('申请失败',50001);
                }

                //活动申请人数自增
                ActivityModel::where('id','=',$activity_id)->setInc('apply_total',1);

                Db::commit();
                //解锁
                flock($handle, LOCK_UN);
                //关闭文件
                fclose($handle);

                return Responses::data(200, 'success',['id'=>$apply_res['id']]);
            }catch (\Exception $e){
                Db::rollback();
                //解锁
                flock($handle, LOCK_UN);
                //关闭文件
                fclose($handle);
                return Responses::data(50001, '申请失败');
            }

        }

    }


    /**
     * 任务提交
     *
     * @return Responses
     * */
    public function submit()
    {

        //表单验证
        $validate = new TaskSubmitValidate();
        $vResult = $validate->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $task_id = Request::post('id',0,'intval');
        $task_info = TaskModel::where('id','=',$task_id)
            ->where('user_id','=',self::$user_id)
            ->find();
        if(!$task_info){
            throw new RequestException( '任务不存在',40003);
        }

        $activity_id = $task_info['activity_id'];
        $activity_info = ActivityModel::where('id','=',$activity_id)
            ->find();

        $data = [];
        $data['text_require'] = Request::post('text_require','','trim,strip_tags,htmlspecialchars');
        $data['audit_end_dt'] = Carbon::now()->addHours($activity_info['audit_cycle'])->toDateTimeString();
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['submit_dt'] = date('Y-m-d H:i:s');
        $data['status'] = 2;//待审核

        if($activity_info['text_require'] && (!$data['text_require'])){
            throw new RequestException( $activity_info['text_require'].'必填',40003);
        }

        //已完成
        if($task_info['status'] == 100){
            throw new RequestException( '任务已完成',40003);
        }

        //待审核
        if($task_info['status'] == 2){
            throw new RequestException( '任务待审核',40003);
        }

        //审核驳回
        if($task_info['status'] == 3){
            throw new RequestException( '任务已审核驳回',40003);
        }

        //复审
        if($task_info['status'] == 4){
            throw new RequestException( '任务已进入复审',40003);
        }

        //举报
        if($task_info['status'] == 5){
            throw new RequestException( '任务已进入举报',40003);
        }

        //已取消
        if($task_info['status'] != 1){
            throw new RequestException( '只能处理待提交任务',40003);
        }

        //已取消
        if(time() - 1 > strtotime($task_info['submit_end_dt'])){
            throw new RequestException( '任务已超时',40003);
        }

        //处理 steps
        $steps = Request::post('steps',[]);
        $stepList = [];
        foreach ($steps as $step){
            if(isset($step['step_id']) && isset($step['image'])){
                $step_id = (int)$step['step_id'];
                $stepList[$step_id] = [
                    'step_id'=>$step_id,
                    'image'=>strip_tags(trim($step['image'])),
                ];
            }
        }

        $activityStepList = ActivityStepModel::where('activity_id','=',$activity_id)->select();
        $newStepList = [];
        foreach ($activityStepList as $step){

            if($step['type'] == 2){

                $item = [];
                $item['activity_id'] = $activity_id;
                $item['task_id'] = $task_id;
                $item['step_id'] = $step['id'];
                $item['type'] = $step['type'];
                $item['num_type'] = 1;
                $item['image'] = $step['image'];
                $item['sub_image'] = '';
                $item['describe'] = $step['describe'];

                if(!isset($stepList[$step['id']])){
                    throw new DbException('有步骤未完成，请刷新页面再提交',50001);
                }

                $row_data = $stepList[$step['id']];
                if(!$row_data['image']){
                    throw new DbException('有收集步骤未上传截图',50001);
                }

                $item['sub_image'] = $row_data['image'];
                $newStepList[] = $item;
            }

        }

        Db::startTrans();
        try{

            $result = TaskModel::where('id','=',$task_id)
                ->where('status','=',1)
                ->update($data);
            if(!$result){
                throw new DbException('提交失败',50001);
            }

            //更新提交总数
            ActivityModel::where('id','=',$activity_id)->setInc('submit_total',1);

            //更新子项
            $step_ids = [];
            foreach ($newStepList as $key=>$step){
                $result = TaskStepModel::create($step);
            }

            Db::commit();
            return Responses::data(200, 'success');

        }catch (Exception $e){

            Db::rollback();
            return Responses::data(50002, '提交失败');

        }

    }


    /**
     * 任务审核
     *
     * @return Responses
     * */
    public function pass()
    {

        //表单验证
        $validate = new TaskAuditValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $task_id = Request::post('id',0,'intval');
        $task_info = TaskModel::where('id','=',$task_id)
            ->where('merchant_id','=',self::$user_id)
            ->find();
        if(!$task_info){
            throw new RequestException( '任务不存在',40003);
        }

        $activity_id = $task_info['activity_id'];
        $activity_info = ActivityModel::where('id','=',$activity_id)
            ->find();

        //已完成
        if($task_info['status'] == 100){
            throw new RequestException( '任务已完成',40003);
        }

        //待审核
        if($task_info['status'] == 4){
            throw new RequestException( '任务已取消',40003);
        }

        //待审核
        if($task_info['status'] == 1){
            throw new RequestException( '任务待提交',40003);
        }

        //已超时
        if(time() - 1 > strtotime($task_info['audit_end_dt'])){
            throw new RequestException( '任务审核已超时',40003);
        }

        $data = [];

        //加锁扣除
        $lock_file = env('root_path').'locks/task_pass_'.$task_id.'_lock.txt';

        $file = fopen($lock_file,"w+");

        //锁定
        if(flock($file,LOCK_EX)){

            $data['status'] = 100;//审核驳回
            $data['audit_dt'] = date('Y-m-d H:i:s');
            $data['finish_dt'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            //复审
            if($data['status'] == 5){
                $data['recheck_status'] = 100;
            }

            //举报
            if($data['status'] == 6){
                $data['report_status'] = 104;
            }

            $user = UserModel::where('id','=',$task_info['user_id'])->find();

            //计算佣金
            $commission_money = get_task_commission_calc($activity_info['price'],$user);
            $data['commission'] = $commission_money;

            Db::startTrans();
            try{

                //修改任务
                $result = TaskModel::where('id','=',$task_id)
                    ->whereIn('status',[2,3,5,6])
                    ->update($data);

                if(!$result){
                    throw new DbException('审核失败.',50001);
                }

                //修改活动
                $result = ActivityModel::where('id','=',$activity_id)->setInc('finish_total',1);

                if(!$result){
                    throw new DbException('审核失败..',50001);
                }

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

                if(!$wallet){
                    throw new Exception('审核失败...',50001);
                }

                //任务完成
                Hook::listen('task_finish',$task_info);

                //重新统计余额
                UserModel::resetTotalAccount($user['id']);

                Db::commit();
                //解锁
                flock($file,LOCK_UN);
                //关闭文件
                fclose($file);

                return Responses::data(200, 'success',['id'=>$wallet['id']]);
            }catch (\Exception $e){
                Db::rollback();
                //解锁
                flock($file,LOCK_UN);
                //关闭文件
                fclose($file);
                return Responses::data(50001, $e->getMessage(),Db::getLastSql());
            }

        }

    }




    /**
     * 任务审核
     *
     * @return Responses
     * */
    public function pass_list()
    {

        //表单验证
        $validate = new TaskAuditValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $task_ids = Request::post('ids',0,'intval');
        $tasks = TaskModel::where('id','in',$task_ids)
            ->where('merchant_id','=',self::$user_id)
            ->select();

        $success_total = 0;
        $error_total = 0;
        if($tasks){
            foreach ($tasks as $task){
                $task_info = $task;
                $task_id = $task['id'];
                $activity_id = $task['activity_id'];

                //待审核
                if($task_info['status'] != 2){
                    $error_total++;
                    continue;
                }

                //已超时
                if(time() - 1 > strtotime($task_info['audit_end_dt'])){
                    $error_total++;
                    continue;
                }

                //加锁扣除
                $lock_file = env('root_path').'locks/task_pass_'.$task_id.'_lock.txt';

                $file = fopen($lock_file,"w+");

                //锁定
                if(flock($file,LOCK_EX)){

                    $data = [];
                    $data['status'] = 100;//审核驳回
                    $data['audit_dt'] = date('Y-m-d H:i:s');
                    $data['finish_dt'] = date('Y-m-d H:i:s');
                    $data['updated_at'] = date('Y-m-d H:i:s');

                    $user = UserModel::where('id','=',$task_info['user_id'])->find();
                    $activity_info = ActivityModel::where('id','=',$activity_id)->find();

                    //计算佣金
                    $commission_money = get_task_commission_calc($activity_info['price'],$user);
                    $data['commission'] = $commission_money;

                    Db::startTrans();
                    try{

                        //修改任务
                        $result = TaskModel::where('id','=',$task_id)
                            ->where('status','=',2)
                            ->update($data);

                        if(!$result){
                            throw new Exception('审核失败.',50001);
                        }

                        //修改活动
                        $result = ActivityModel::where('id','=',$activity_id)->setInc('finish_total',1);

                        if(!$result){
                            throw new Exception('审核失败..',50001);
                        }

                        //写入付款
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

                        if(!$wallet){
                            throw new Exception('审核失败...',50001);
                        }

                        //任务完成
                        Hook::listen('task_finish',$task_info);

                        //重新统计余额
                        UserModel::resetTotalAccount($user['id']);

                        Db::commit();
                        //解锁
                        flock($file,LOCK_UN);
                        //关闭文件
                        fclose($file);

                        $success_total++;

                    }catch (\Exception $e){
                        Db::rollback();
                        //解锁
                        flock($file,LOCK_UN);
                        //关闭文件
                        fclose($file);
                        $error_total++;
                    }

                }


            }
        }

        return Responses::data(200, 'success',compact('success_total','error_total'));
    }


    /**
     * 任务审核
     *
     * @return Responses
     * */
    public function reject()
    {

        //表单验证
        $validate = new TaskAuditValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $task_id = Request::post('id',0,'intval');
        $task_info = TaskModel::where('id','=',$task_id)
            ->where('merchant_id','=',self::$user_id)
            ->find();
        if(!$task_info){
            throw new RequestException( '任务不存在',40003);
        }

        //已完成
        if($task_info['status'] == 100){
            throw new RequestException( '任务已完成',40003);
        }

        //待审核
        if($task_info['status'] == 4){
            throw new RequestException( '任务已取消',40003);
        }

        //待审核
        if($task_info['status'] == 1){
            throw new RequestException( '任务待提交',40003);
        }

        //已超时
        if(time() - 1 > strtotime($task_info['audit_end_dt'])){
            throw new RequestException( '任务审核已超时',40003);
        }

        //待审核
        if($task_info['status'] != 2){
            throw new RequestException( '任务已初审',40003);
        }

        $data = [];
        $data['status'] = 3;//审核驳回
        //驳回原因
        $data['audit_reason'] = [
            'text'=>Request::post('text','','trim,strip_tags,htmlspecialchars'),
            'images'=>Request::post('images',[])
        ];

        //复审开启倒计时
        $data['recheck_submit_end_dt'] = Carbon::now()->addHours(24)->toDateTimeString();
        $data['audit_dt'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        try{

            $result = TaskModel::where('id','=',$task_id)
                ->where('status','=',2)
                ->update($data);

            if(!$result){
                throw new DbException('审核失败',50001);
            }

            return Responses::data(200, 'success');

        }catch (Exception $e){

            return Responses::data(50002, '审核失败');

        }
    }


    /**
     * 复审提交
     *
     * @return Responses
     * */
    public function recheck_submit()
    {

        //表单验证
        $validate = new TaskSubmitValidate();
        $vResult = $validate->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $task_id = Request::post('id',0,'intval');
        $task_info = TaskModel::where('id','=',$task_id)
            ->where('user_id','=',self::$user_id)
            ->find();
        if(!$task_info){
            throw new RequestException( '任务不存在',40003);
        }

        $activity_id = $task_info['activity_id'];
        $activity_info = ActivityModel::where('id','=',$activity_id)
            ->find();

        $data = [];
        $data['recheck_text_require'] = Request::post('text_require','','trim,strip_tags,htmlspecialchars');
        $data['recheck_audit_end_dt'] = Carbon::now()->addHours($activity_info['audit_cycle'])->toDateTimeString();
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['recheck_submit_dt'] = date('Y-m-d H:i:s');
        $data['status'] = 5;//复审
        $data['recheck_status'] = 2;//待审核

        if($activity_info['text_require'] && (!$data['recheck_text_require'])){
            throw new RequestException( $activity_info['text_require'].'必填',40003);
        }

        //已完成
        if($task_info['status'] == 100){
            throw new RequestException( '任务已完成',40003);
        }

        //待审核
        if($task_info['status'] == 2){
            throw new RequestException( '任务待审核',40003);
        }

        //复审
        if($task_info['status'] == 5){
            throw new RequestException( '任务已进入复审',40003);
        }

        //举报
        if($task_info['status'] == 6){
            throw new RequestException( '任务已进入举报',40003);
        }

        //初审驳回
        if($task_info['status'] != 3){
            throw new RequestException( '只能提交初审驳回任务',40003);
        }

        //已取消
        if(time() - 1 > strtotime($task_info['recheck_submit_end_dt'])){
            throw new RequestException( '任务已超时',40003);
        }

        //处理 steps
        $steps = Request::post('steps',[]);
        $stepList = [];
        foreach ($steps as $step){
            if(isset($step['step_id']) && isset($step['image'])){
                $step_id = (int)$step['step_id'];
                $stepList[$step_id] = [
                    'step_id'=>$step_id,
                    'image'=>strip_tags(trim($step['image'])),
                ];
            }
        }

        $activityStepList = ActivityStepModel::where('activity_id','=',$activity_id)->select();
        $newStepList = [];
        foreach ($activityStepList as $step){

            if($step['type'] == 2){

                $item = [];
                $item['activity_id'] = $activity_id;
                $item['task_id'] = $task_id;
                $item['step_id'] = $step['id'];
                $item['type'] = $step['type'];
                $item['num_type'] = 2;
                $item['image'] = $step['image'];
                $item['sub_image'] = '';
                $item['describe'] = $step['describe'];

                if(!isset($stepList[$step['id']])){
                    throw new DbException('有步骤未完成，请刷新页面再提交',50001);
                }

                $row_data = $stepList[$step['id']];
                if(!$row_data['image']){
                    throw new DbException('有收集步骤未上传截图',50001);
                }

                $item['sub_image'] = $row_data['image'];
                $newStepList[] = $item;
            }

        }

        Db::startTrans();
        try{

            $result = TaskModel::where('id','=',$task_id)
                ->where('status','=',3)
                ->update($data);
            if(!$result){
                throw new DbException('提交失败',50001);
            }

            //更新子项
            $step_ids = [];
            foreach ($newStepList as $key=>$step){
                TaskStepModel::create($step);
            }

            Db::commit();
            return Responses::data(200, 'success');

        }catch (Exception $e){

            Db::rollback();
            return Responses::data(50002, '提交失败');

        }

    }


    /**
     * 复审驳回
     *
     * @return Responses
     * */
    public function recheck_reject()
    {

        //表单验证
        $validate = new TaskAuditValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $task_id = Request::post('id',0,'intval');
        $task_info = TaskModel::where('id','=',$task_id)
            ->where('merchant_id','=',self::$user_id)
            ->find();
        if(!$task_info){
            throw new RequestException( '任务不存在',40003);
        }

        //已完成
        if($task_info['status'] == 100){
            throw new RequestException( '任务已完成',40003);
        }

        //待审核
        if($task_info['status'] == 4){
            throw new RequestException( '任务已取消',40003);
        }

        //待审核
        if($task_info['status'] == 1){
            throw new RequestException( '任务待提交',40003);
        }

        //举报
        if($task_info['status'] == 6){
            throw new RequestException( '任务已举报',40003);
        }

        //待复审
        if($task_info['status'] != 5){
            throw new RequestException( '任务不是复审',40003);
        }

        if($task_info['recheck_status'] != 2){
            $recheck_status_list = [1=>'待提交',2=>'待审核',3=>'已驳回',100=>'已通过'];
            throw new RequestException( '任务复审'.$recheck_status_list[$task_info['recheck_status']],40003);
        }

        //已超时
        if(time() - 1 > strtotime($task_info['recheck_audit_end_dt'])){
            throw new RequestException( '任务复审已超时',40003);
        }

        $data = [];
        $data['status'] = 5;//审核驳回
        $data['recheck_status'] = 3;//审核驳回
        //驳回原因
        $data['recheck_reason'] = [
            'text'=>Request::post('text','','trim,strip_tags,htmlspecialchars'),
            'images'=>Request::post('images',[])
        ];

        //举报开启倒计时
        $data['report_open_end_dt'] = Carbon::now()->addHours(24)->toDateTimeString();
        $data['recheck_audit_dt'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        try{

            $result = TaskModel::where('id','=',$task_id)
                ->where('status','=',5)
                ->where('recheck_status','=',2)
                ->update($data);

            if(!$result){
                throw new DbException('审核失败',50001);
            }

            return Responses::data(200, 'success');

        }catch (Exception $e){

            return Responses::data(50002, '审核失败');

        }
    }




    /**
     * 举报提交
     *
     * @return Responses
     * */
    public function report_create()
    {

        //表单验证
        $validate = new TaskReportValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $task_id = Request::post('id',0,'intval');


        $Task = TaskModel::where('id','=',$task_id);
        $identity = Request::post('identity',1,'intval');

        //商家
        if($identity == 1){
            $Task->where('merchant_id','=',self::$user_id);
        }else if($identity == 1){
            $Task->where('user_id','=',self::$user_id);
        }

        $task_info = $Task->find();

        if(!$task_info){
            throw new RequestException( '任务不存在',40003);
        }

        //已完成
        if($task_info['status'] == 100){
            throw new RequestException( '任务已完成',40003);
        }

        //待提交
        if($task_info['status'] == 1){
            throw new RequestException( '任务待提交',40003);
        }

        //待审核
        if($task_info['status'] == 2){
            throw new RequestException( '任务待审核',40003);
        }

        //初审已驳回
        if($task_info['status'] == 3){
            throw new RequestException( '任务待复审',40003);
        }

        //取消
        if($task_info['status'] == 4){
            throw new RequestException( '任务已取消',40003);
        }

        //举报
        if($task_info['status'] == 6){
            throw new RequestException( '任务已进入举报',40003);
        }

        //复审驳回
        if($task_info['status'] != 5){
            throw new RequestException( '只能提交复审驳回任务',40003);
        }

        //初审驳回
        if($task_info['recheck_status'] != 3){
            throw new RequestException( '只能举报复审驳回的任务',40003);
        }

        //已取消
        if(time() - 1 > strtotime($task_info['report_open_end_dt'])){
            throw new RequestException( '任务已超时',40003);
        }

        $data = [];
        $data['status'] = 6;//举报
        $data['report_status'] = 1;//待辩诉
        $data['report_identity'] = $identity;//身份
        //举报原因
        $data['report_data'] = [
            'text'=>Request::post('text','','trim,strip_tags,htmlspecialchars'),
            'images'=>Request::post('images',[])
        ];

        //答辩倒计时
        $data['report_bl_end_dt'] = Carbon::now()->addHours(24)->toDateTimeString();
        $data['report_submit_dt'] = date('Y-m-d H:i:s');

        try{

            $result = TaskModel::where('id','=',$task_id)
                ->where('status','=',5)
                ->where('recheck_status','=',3)
                ->update($data);
            if(!$result){
                throw new DbException('提交失败',50001);
            }

            return Responses::data(200, 'success');

        }catch (Exception $e){

            return Responses::data(50002, '提交失败');

        }

    }




    /**
     * 举报辩诉
     *
     * @return Responses
     * */
    public function report_argue()
    {

        //表单验证
        $validate = new TaskReportValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $task_id = Request::post('id',0,'intval');


        $Task = TaskModel::where('id','=',$task_id);
        $identity = Request::post('identity',1,'intval');

        //商家
        if($identity == 1){
            $Task->where('merchant_id','=',self::$user_id);
        }else if($identity == 1){
            $Task->where('user_id','=',self::$user_id);
        }

        $task_info = $Task->find();

        if(!$task_info){
            throw new RequestException( '任务不存在',40003);
        }

        //已完成
        if($task_info['status'] == 100){
            throw new RequestException( '任务已完成',40003);
        }

        //待提交
        if($task_info['status'] == 1){
            throw new RequestException( '任务待提交',40003);
        }

        //待审核
        if($task_info['status'] == 2){
            throw new RequestException( '任务待审核',40003);
        }

        //初审已驳回
        if($task_info['status'] == 3){
            throw new RequestException( '任务待复审',40003);
        }

        //取消
        if($task_info['status'] == 4){
            throw new RequestException( '任务已取消',40003);
        }

        //复审
        if($task_info['status'] == 5){
            throw new RequestException( '任务已进入复审',40003);
        }

        //举报
        if($task_info['status'] != 6){
            throw new RequestException( '只能举报复审驳回的任务',40003);
        }

        //举报辩论
        if($task_info['report_status'] != 1){
            throw new RequestException( '只能辩论待辩论的任务',40003);
        }

        //已取消
        if(time() - 1 > strtotime($task_info['report_bl_end_dt'])){
            throw new RequestException( '任务已超时',40003);
        }

        $data = [];
        $data['report_status'] = 2;//待平台处理
        //辩论原因
        $data['report_bl_data'] = [
            'text'=>Request::post('text','','trim,strip_tags,htmlspecialchars'),
            'images'=>Request::post('images',[])
        ];

        //答辩倒计时
        $data['report_bl_dt'] = date('Y-m-d H:i:s');

        try{

            $result = TaskModel::where('id','=',$task_id)
                ->where('status','=',6)
                ->where('report_status','=',1)
                ->update($data);
            if(!$result){
                throw new DbException('提交失败',50001);
            }

            return Responses::data(200, 'success');

        }catch (Exception $e){

            return Responses::data(50002, '提交失败');

        }

    }




    /**
     * 取消任务
     *
     * @return Responses
     * */
    public function cancel()
    {

        //表单验证
        $validate = new TaslValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id',0,'intval');
        $task_info = TaskModel::where('id','=',$id)
            ->where('user_id','=',self::$user_id)
            ->find();
        if(!$task_info){
            throw new RequestException( '任务不存在',40003);
        }

        //任务完成
        if($task_info['status'] == 100){
            throw new RequestException( '任务已完成，不能取消',40003);
        }

        //任务已取消
        if($task_info['status'] == 4){
            throw new RequestException( '任务已取消，不能重复取消',40003);
        }

        Db::startTrans();
        try{

            //更新任务
            TaskModel::where('id','=',$id)
                ->where('status','<>',100)
                ->update([
                    'status'=>4,
                    'reason'=>'手动取消',
                    'updated_at'=>date('Y-m-d H:i:s'),
                ]);

            //更新任务
            ActivityModel::where('id','=',$task_info['activity_id'])
                ->update([
                    'updated_at'=>date('Y-m-d H:i:s'),
                    'cancel_total'=>Db::raw('cancel_total+1')
                ]);

            Db::commit();
            return Responses::data(200, 'success');

        }catch (Exception $e){

            Db::rollback();
            return Responses::data(50002, '取消失败');

        }

    }




    /**
     * 删除任务
     *
     * @return Responses
     * */
    public function delete()
    {

        //表单验证
        $validate = new TaslValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id',0,'intval');
        $task_info = TaskModel::where('id','=',$id)
            ->where('user_id','=',self::$user_id)
            ->find();
        if(!$task_info){
            throw new RequestException( '任务不存在',40003);
        }

        //任务已取消
        if($task_info['status'] != 4){
            throw new RequestException( '只能删除已取消的任务',40003);
        }

        Db::startTrans();
        try{

            //更新任务
            TaskModel::where('id','=',$id)
                ->where('status','=',4)
                ->update([
                    'user_delete'=>1,
                    'updated_at'=>date('Y-m-d H:i:s'),
                ]);

            Db::commit();
            return Responses::data(200, 'success');

        }catch (Exception $e){

            Db::rollback();
            return Responses::data(50002, '删除失败');

        }

    }


}
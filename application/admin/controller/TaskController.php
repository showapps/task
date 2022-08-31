<?php
/**
 * ArticleController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/17
 */

namespace app\admin\controller;


use app\admin\traits\AuthTrait;
use app\admin\validate\ActivityRefereeValidate;
use app\admin\validate\TaskValidate;
use app\common\exception\DbException;
use app\common\exception\Exception;
use app\common\exception\RequestException;
use app\common\model\ActivityCategoryModel;
use app\common\model\ActivityModel;
use app\common\model\TaskModel;
use app\common\model\UserModel;
use app\common\model\WalletModel;
use think\Db;
use think\facade\Hook;
use think\facade\Request;
use think\facade\View;
use Tools\Responses;

class TaskController
{

    use AuthTrait;

    protected $directory = 'task';

    public function __construct()
    {
        $this->initAuthInfo();
    }





    public function index()
    {
        $categorys = ActivityCategoryModel::field('id,title')->all();
        return view($this->directory . '/index',compact('categorys'));
    }




    /**
     * 数据列表
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
                        $value = intval($value);
                        if($value){
                            $TaskModel = $TaskModel->where('category_id','=',$value);
                        }
                        break;
                    case 'search_text':
                        $value = htmlspecialchars(strip_tags(trim($value)));
                        if($value){
                            $TaskModel = $TaskModel->where('(id='.$value.' OR merchant_id='.$value.' OR activity_id='.$value.' OR user_id='.$value.')');
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $TaskModel
            ->with([
                'category'=>function($query){
                    $query->field('id,title');
                },
                'merchant'=>function($query){
                    $query->field('id,nick_name');
                },
                'activity'=>function($query){

                },
                'user'=>function($query){
                    $query->field('id,nick_name');
                }
            ])
            ->order('id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = [];
        if($lists['data']){
            foreach ($lists['data'] as $data){
                $data['activity']['price'] = fen_to_float($data['activity']['price']);
                $datas[] = $data;
            }
        }

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


    /**
     * 数据详情
     *
     * @return Responses
     * */
    public function detail()
    {

        //表单验证
        $validate = new TaskValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::param());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::param('id');
        $info = TaskModel::where('id','=',$id)->with([
            'category'=>function($query){
                $query->field('id,title');
            },
            'merchant'=>function($query){
                $query->field('id,nick_name');
            },
            'activity',
            'user'=>function($query){
                $query->field('id,nick_name');
            },
            'steps'=>function($query){
                $query->field('id,activity_id,type,image,describe');
            },
            'task_steps'=>function($query){
                $query->field('id,task_id,step_id,type,num_type,image,sub_image,describe');
                $query->where('type','=',2);
            }
        ])->find();
        if(!$info){
            throw new RequestException( '任务不存在',40401);
        }

        $info['activity']['price'] = fen_to_float($info['activity']['price']);

        $data = (string)View::fetch($this->directory . '/detail',compact('info'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 审核模板
     *
     * @return Responses
     * */
    public function referee_view()
    {

        //表单验证
        $validate = new TaskValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = TaskModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '任务不存在',40401 );
        }

        if($info['status'] != 6){
            throw new RequestException( '任务不是举报状态',40401 );
        }

        if($info['report_status'] != 2){
            throw new RequestException( '任务不是平台裁判状态',40401 );
        }

        $data = (string)View::fetch($this->directory . '/referee',compact('info'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 审核活动
     *
     * @return Responses
     * */
    public function referee()
    {

        //表单验证
        $validate = new ActivityRefereeValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $task_id = $id;
        $info = TaskModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '任务不存在',40401 );
        }

        $activity_id = $info['activity_id'];

        if($info['status'] != 6){
            throw new RequestException( '任务不是举报状态',40401 );
        }

        if($info['report_status'] != 2){
            throw new RequestException( '任务不是平台裁判状态',40401 );
        }

        $type = Request::post('type',1,'intval');
        //驳回理由
        $reasons = Request::post('reasons','','trim,strip_tags,htmlspecialchars');
        //商家胜诉
        if($type == 1){

            $data = [];
            $data['report_status'] = 101;//待平台处理
            //驳回原因
            $data['report_reason'] = json_encode([
                'text'=>$reasons,
                'images'=>[]
            ]);

            //平台处理时间
            $data['report_audit_dt'] = date('Y-m-d H:i:s');
            $data['finish_dt'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            try{

                $result = TaskModel::where('id','=',$id)
                    ->where('status','=',6)
                    ->where('report_status','=',2)
                    ->update($data);
                if(!$result){
                    throw new DbException('审核失败',50001);
                }

                ActivityModel::where('id','=',$info['activity_id'])->setInc('cancel_total',1);

                return Responses::data(200, 'success');

            }catch (Exception $e){

                return Responses::data(50002, $e->getMessage());

            }

        //接单者胜诉
        }else{

            //加锁扣除
            $lock_file = env('root_path').'locks/task_pass_'.$task_id.'_lock.txt';

            $file = fopen($lock_file,"w+");

            //锁定
            if(flock($file,LOCK_EX)){

                $data['report_status'] = 100;//接单者胜诉
                //驳回原因
                $data['report_reason'] = json_encode([
                    'text'=>$reasons,
                    'images'=>[]
                ]);

                //平台处理时间
                $data['report_audit_dt'] = date('Y-m-d H:i:s');
                $data['finish_dt'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');

                $task_info = TaskModel::where('id','=',$info['id'])->find();
                $user = UserModel::where('id','=',$info['user_id'])->find();
                $activity_info =  ActivityModel::where('id','=',$activity_id)->find();
                Db::startTrans();

                try{
                    //计算佣金
                    $commission_money = get_task_commission_calc($activity_info['price'],$user);
                    $data['commission'] = $commission_money;
                    //修改任务
                    $result = TaskModel::where('id','=',$task_id)
                        ->where('status','=',6)
                        ->where('report_status','=',2)
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

                    //重新统计余额
                    UserModel::resetTotalAccount($user['id']);

                    //任务完成
                    Hook::listen('task_finish',$task_info);

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
                    return Responses::data(50001, $e->getMessage());
                }

            }

        }

    }


}
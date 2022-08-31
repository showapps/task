<?php


namespace app\mobile\controller;


use app\common\exception\DbException;
use app\common\exception\RequestException;
use app\common\model\ActivityModel;
use app\common\model\TaskModel;
use app\common\model\UserFollowModel;
use app\common\model\UserModel;
use app\mobile\traits\AuthTrait;
use app\mobile\validate\UserValidate;
use think\facade\Request;
use Tools\Responses;

class UserController
{

    use AuthTrait;

    /**
     * 数据列表
     *
     * @return Responses
     * */
    public function lists()
    {

        $UserModel = new UserModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $UserModel = $UserModel->where('id','in',$value);
                        }
                        break;
                    case 'parent_id':
                        $value = intval($value);
                        if($value){
                            $UserModel = $UserModel->where('parent_id','=',$value);
                        }
                        break;
                    case 'parent2_id':
                        $value = intval($value);
                        if($value){
                            $UserModel = $UserModel->where('parent2_id','=',$value);
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $UserModel = $UserModel->where('status','in',$value);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $UserModel
            ->paginate($page_size)
            ->toArray();

        $datas = $lists['data'];

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
        $validate = new UserValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id',0,'intval');
        $info = UserModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '用户不存在',40402);
        }

        $info['activity_total'] = ActivityModel::where('merchant_id','=',$id)
            ->where('status','=',100)
            ->count();
        $info['task_total'] = TaskModel::where('user_id','=',$id)
            ->whereIn('status',[1,2,3,5,6,100])
            ->count();
        $info['fans_total'] = UserFollowModel::where('followed_user_id','=',$id)->count();
        $info['follow_total'] = UserFollowModel::where('user_id','=',$id)->count();

        return Responses::data(200, 'success',$info);

    }


    /**
     * 粉丝列表
     *
     * @return Responses
     * */
    public function fansList()
    {

        $this->initAuthInfo();
        $UserFollowModel = new UserFollowModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'user_id':
                        $value = intval($value);
                        if($value){
                            $UserFollowModel = $UserFollowModel->where('user_id','=',$value);
                        }
                        break;
                    case 'followed_user_id':
                        $value = intval($value);
                        if($value){
                            $UserFollowModel = $UserFollowModel->where('followed_user_id','=',$value);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $UserFollowModel
            ->with([
                'user'=>function($query){
                    $query->field('id,nick_name,true_name,avatar');
                },
                'followed_user'=>function($query){
                    $query->field('id,nick_name,true_name,avatar');
                }
            ])
            ->order('id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = [];
        if($lists['data']){
            foreach ($lists['data'] as $data){

                if($data['user']){
                    $data['user']['is_follow'] = is_follow(self::$user_id,$data['user']['id']);
                }

                if($data['followed_user']){
                    $data['followed_user']['is_follow'] = is_follow(self::$user_id,$data['followed_user']['id']);;
                }

                $datas[] = $data;
            }
        }

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


}
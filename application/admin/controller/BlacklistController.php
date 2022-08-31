<?php


namespace app\admin\controller;


use app\admin\traits\AuthTrait;
use app\admin\validate\BlacklistValidate;
use app\common\exception\DbException;
use app\common\exception\RequestException;
use app\common\model\BlacklistModel;
use app\common\model\TokenModel;
use app\common\model\UserModel;
use think\facade\Request;
use think\facade\View;
use Tools\Responses;

class BlacklistController
{

    use AuthTrait;

    protected $directory = 'blacklist';

    public function __construct()
    {
        $this->initAuthInfo();
    }





    public function index()
    {
        return view($this->directory . '/index');
    }




    /**
     * 数据列表
     *
     * @return Responses
     * */
    public function lists()
    {

        $BlacklistModel = new BlacklistModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $BlacklistModel = $BlacklistModel->where('id','in',$value);
                        }
                        break;
                    case 'search_text':
                        $value = htmlspecialchars(strip_tags(trim($value)));
                        if($value){
                            $user_ids = UserModel::where("(ID='{$value}' OR true_name='{$value}' OR user_name='{$value}' OR nick_name='{$value}' OR phone='{$value}' OR email='{$value}')")
                            ->column('id');
                            if($user_ids){
                                $user_ids = is_array($user_ids) ? $user_ids : [$user_ids];
                                $BlacklistModel = $BlacklistModel->whereIn('user_id',$user_ids);
                            }else{
                                $BlacklistModel = $BlacklistModel->where('user_id','=',0);
                            }
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $BlacklistModel
            ->with(['user'=>function($query){
                $query->field('id,nick_name,true_name,phone');
            }])
            ->order('id DESC')
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
        $validate = new BlacklistValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::param());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = BlacklistModel::where('id','=',$id)->with(['user'=>function($query){
            $query->field('id,nick_name,true_name,phone');
        }])->find();
        if(!$info){
            throw new RequestException( '黑名单不存在',40401);
        }

        $data = (string)View::fetch($this->directory . '/detail',compact('info'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 添加数据
     *
     * @return Responses
     * */
    public function create_view()
    {

        $data = (string)View::fetch($this->directory . '/add');
        return Responses::data(200, 'success',$data);

    }


    /**
     * 创建数据
     *
     * @return Responses
     * */
    public function create()
    {

        //表单验证
        $validate = new BlacklistValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $data = [];
        $data['user_id'] = Request::post('user_id',0,'intval');
        $data['content'] = Request::post('content','','trim,strip_tags,htmlspecialchars');
        $data['authoritys'] = Request::post('authoritys',[]);
        sort($data['authoritys']);
        $data['end_dt'] = Request::post('end_dt','','trim');
        $data['status'] = 1;

        try {


            $info = BlacklistModel::create($data);
            if(!$info){
                throw new DbException('数据添加失败',50001);
            }

            //移除其他黑名单
            BlacklistModel::where('user_id','=',$data['user_id'])
                ->where('id','<>',$info['id'])
                ->delete();

            TokenModel::where('guard','=','mobile')
                ->where('user_id','=',$data['user_id'])
                ->delete();

            return Responses::data(200, 'success',['id'=>$info->id]);

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


    /**
     * 更新数据
     *
     * @return Responses
     * */
    public function update_view()
    {

        //表单验证
        $validate = new BlacklistValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = BlacklistModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '数据不存在',40401 );
        }

        $data = (string)View::fetch($this->directory . '/mod',compact('info'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 更新数据
     *
     * @return Responses
     * */
    public function update()
    {

        //表单验证
        $validate = new BlacklistValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id',0,'intval');
        $info = BlacklistModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '数据不存在',40401 );
        }

        $data = [];
        $data['content'] = Request::post('content','','trim,strip_tags,htmlspecialchars');
        $data['authoritys'] = Request::post('authoritys',[]);
        sort($data['authoritys']);
        $data['end_dt'] = Request::post('end_dt','','trim');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['status'] = 1;

        TokenModel::where('guard','=','mobile')
            ->where('user_id','=',$info['user_id'])
            ->delete();

        try {

            $result = BlacklistModel::where('id','=',$id)->update($data);
            if(!$result){
                throw new DbException('更新失败',50001);
            }

            return Responses::data(200, 'success',['id'=>$id]);

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


    /**
     * 删除数据
     *
     * @return Responses
     * */
    public function delete()
    {

        //表单验证
        $validate = new BlacklistValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }
        $id = Request::post('id');
        $info = BlacklistModel::where('id','=',$id)->find();
        if($info){
            TokenModel::where('guard','=','mobile')
                ->where('user_id','=',$info['user_id'])
                ->delete();
        }

        BlacklistModel::where('id','=',$id)->delete();
        return Responses::data(200, 'success');

    }


}
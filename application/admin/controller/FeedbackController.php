<?php

namespace app\admin\controller;


use app\admin\traits\AuthTrait;
use app\admin\validate\BlacklistValidate;
use app\admin\validate\FeedbackValidate;
use app\common\exception\DbException;
use app\common\exception\RequestException;
use app\common\model\BlacklistModel;
use app\common\model\FeedbackModel;
use app\common\model\TokenModel;
use think\facade\Request;
use think\facade\View;
use Tools\Responses;

class FeedbackController
{

    use AuthTrait;

    protected $directory = 'feedback';

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

        $FeedbackModel = new FeedbackModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $FeedbackModel = $FeedbackModel->where('id','in',$value);
                        }
                        break;
                    case 'status':
                        if($value){
                            $FeedbackModel = $FeedbackModel->where('status','=',$value);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $FeedbackModel
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
        $validate = new FeedbackValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::param());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = FeedbackModel::where('id','=',$id)->with(['user'=>function($query){
            $query->field('id,nick_name,true_name,phone');
        }])->find();
        if(!$info){
            throw new RequestException( '反馈不存在',40401);
        }

        $data = (string)View::fetch($this->directory . '/detail',compact('info'));
        return Responses::data(200, 'success',$data);

    }



    /**
     * 更新数据
     *
     * @return Responses
     * */
    public function update_view()
    {

        //表单验证
        $validate = new FeedbackValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = FeedbackModel::where('id','=',$id)->with(['user'=>function($query){
            $query->field('id,nick_name,true_name,phone');
        }])->find();
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
        $validate = new FeedbackValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id',0,'intval');
        $info = FeedbackModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '数据不存在',40401 );
        }

        $data = [];
        $data['result'] = Request::post('result','','trim,strip_tags,htmlspecialchars');
        $data['status'] = Request::post('status','','intval') == 100 ? 100 : 2;

        try {

            $result = FeedbackModel::where('id','=',$id)->update($data);
            if(!$result){
                throw new DbException('操作失败',50001);
            }

            return Responses::data(200, 'success',['id'=>$id]);

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }



}
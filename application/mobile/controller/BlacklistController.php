<?php


namespace app\mobile\controller;


use app\common\exception\RequestException;
use app\common\model\BlacklistModel;
use app\mobile\validate\BlacklistValidate;
use think\facade\Request;
use Tools\Responses;

class BlacklistController
{


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
                    case 'user_ids':
                        if($value && is_array($value)){
                            $BlacklistModel = $BlacklistModel->where('user_id','in',$value);
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $BlacklistModel = $BlacklistModel->where('status','in',$value);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $BlacklistModel
            ->with([
                'user'=>function($query){
                    $query->field('id,nick_name,true_name,phone');
                }
            ])
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
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id',0,'intval');
        $info = BlacklistModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '页面不存在',40402);
        }

        return Responses::data(200, 'success',$info);

    }


}
<?php


namespace app\mobile\controller;


use app\common\exception\DbException;
use app\common\exception\RequestException;
use app\common\model\HelpCategoryModel;
use app\common\model\HelpModel;
use app\mobile\validate\HelpValidate;
use think\facade\Request;
use Tools\Responses;

class HelpController
{


    /**
     * 帮助列表
     *
     * @return Responses
     * */
    public function lists()
    {

        $HelpModel = new HelpModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $HelpModel = $HelpModel->where('id','in',$value);
                        }
                        break;
                    case 'category_id':
                        $value = (int)$value;
                        if($value){
                            $HelpModel = $HelpModel->where('category_id','=',$value);
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $HelpModel = $HelpModel->where('status','in',$value);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $HelpModel
            ->order('sort DESC,id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = $lists['data'];

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


    /**
     * 分类列表
     *
     * @return Responses
     * */
    public function categorys()
    {

        $HelpCategoryModel = new HelpCategoryModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $HelpCategoryModel = $HelpCategoryModel->where('id','in',$value);
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $HelpCategoryModel = $HelpCategoryModel->where('status','in',$value);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $HelpCategoryModel
            ->order('sort DESC,id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = $lists['data'];

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


    /**
     * 帮助详情
     *
     * @return Responses
     * */
    public function detail()
    {

        //表单验证
        $validate = new HelpValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = HelpModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '帮助不存在',40402);
        }

        return Responses::data(200, 'success',$info);

    }


}
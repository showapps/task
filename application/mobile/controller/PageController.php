<?php


namespace app\mobile\controller;


use app\common\exception\DbException;
use app\common\exception\RequestException;
use app\common\model\PageModel;
use app\mobile\validate\PageValidate;
use think\facade\Request;
use Tools\Responses;

class PageController
{


    /**
     * 数据列表
     *
     * @return Responses
     * */
    public function lists()
    {

        $PageModel = new PageModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $PageModel = $PageModel->where('id','in',$value);
                        }
                        break;
                    case 'names':
                        if($value && is_array($value)){
                            $PageModel = $PageModel->where('name','in',$value);
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $PageModel = $PageModel->where('status','in',$value);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $PageModel
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
        $validate = new PageValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $name = Request::post('name','','trim,htmlspecialchars');
        $info = PageModel::where('name','=',$name)->find();
        if(!$info){
            throw new RequestException( '页面不存在',40402);
        }

        return Responses::data(200, 'success',$info);

    }


}
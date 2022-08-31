<?php


namespace app\mobile\controller;


use app\common\exception\RequestException;
use app\common\model\AdModel;
use app\mobile\validate\AdValidate;
use think\facade\Request;
use Tools\Responses;

class AdController
{


    /**
     * 广告列表
     *
     * @return Responses
     * */
    public function lists()
    {

        $AdModel = new AdModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $AdModel = $AdModel->where('id','in',$value);
                        }
                        break;
                    case 'positions':
                        if($value && is_array($value)){
                            $AdModel = $AdModel->where('position','in',$value);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $AdModel
            ->where('status','=',1)
            ->order('sort DESC,id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = $lists['data'];

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


    /**
     * 广告详情
     *
     * @return Responses
     * */
    public function detail()
    {

        //表单验证
        $validate = new AdValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $position = Request::post('position','','trim,htmlspecialchars');
        $info = AdModel::where('position','=',$position)->find();
        if(!$info){
            throw new RequestException( '广告不存在',40402);
        }

        return Responses::data(200, 'success',$info);

    }


}
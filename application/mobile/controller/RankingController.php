<?php

namespace app\mobile\controller;


use app\common\model\RankingListModel;
use app\mobile\traits\AuthTrait;
use app\mobile\traits\ModuleTrait;
use think\facade\Request;
use Tools\Auth;
use Tools\Responses;

class RankingController
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

        $RankingListModel = new RankingListModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $RankingListModel = $RankingListModel->where('id','in',$value);
                        }
                        break;
                    case 'type':
                        if($value && is_array($value)){
                            $RankingListModel = $RankingListModel->where('type','in',$value);
                        }
                        break;
                    case 'month':

                        if($value == '--ThisMonth--'){
                            $value = date("Ym");
                        }

                        $value = intval($value);
                        if($value){
                            $RankingListModel = $RankingListModel->where('month','=',$value);
                        }
                        break;
                }
            }
        }


        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $RankingListModel
            ->with([
                'user'=>function($query){
                    $query->field('id,nick_name,true_name,avatar');
                }
            ])
            ->order('index ASC')
            ->paginate($page_size)
            ->toArray();

        $datas = [];
        if($lists['data']){
            foreach ($lists['data'] as $data){

                $data['reward'] = fen_to_float($data['reward']);
                $datas[] = $data;

            }
        }

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }
}
<?php


namespace app\admin\controller;

use app\admin\traits\AuthTrait;
use app\common\model\RankingListModel;
use think\facade\Request;
use Tools\Responses;

class RankinglistController
{

    use AuthTrait;

    protected $directory = 'rankinglist';

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
                        if($value){
                            $RankingListModel = $RankingListModel->where('type','=',$value);
                        }
                        break;
                    case 'months':
                        if($value){
                            $months = explode(' - ',$value);
                            $start_month = str_replace('-','',$months[0]);
                            $end_month = str_replace('-','',$months[1]);
                            $RankingListModel = $RankingListModel->whereBetween('month',[$start_month,$end_month]);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $RankingListModel
            ->with(['user'=>function($query){
                $query->field('id,nick_name,true_name,phone');
            }])
            ->order('month DESC,index ASC')
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

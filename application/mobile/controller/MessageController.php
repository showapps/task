<?php


namespace app\mobile\controller;


use app\common\model\MessageModel;
use app\mobile\traits\AuthTrait;
use think\facade\Request;
use Tools\Responses;

class MessageController
{

    use AuthTrait;

    public function __construct()
    {
        $this->initAuthInfo();
    }


    public function lists(){


        $MessageModel = new MessageModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $MessageModel = $MessageModel->where('id','in',$value);
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $MessageModel = $MessageModel->where('status','in',$value);
                        }
                        break;
                }
            }
        }

        $MessageModel = $MessageModel->where('user_id','=',self::$user_id);

        $page_size = Request::post('page_size',env('page_size',10));
        $lists = $MessageModel
            ->order('id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = [];
        if($lists['data']){
            foreach ($lists['data'] as $data){

                //设为已读
                if($data['status'] == 2){
                    MessageModel::where('id','=',$data['id'])->update([
                        'status'=>1,
                        'updated_at'=>date('Y-m-d H:i:s'),
                    ]);
                }

                $data['send_dt'] = date('Y年m月d日 H:i',strtotime($data['created_at']));
                $datas[] = $data;
            }
        }

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


}
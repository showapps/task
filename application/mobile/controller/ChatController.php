<?php

namespace app\mobile\controller;


use app\common\exception\RequestException;
use app\common\model\ChatListModel;
use app\common\model\ChatModel;
use app\common\model\TaskModel;
use app\common\model\UserModel;
use app\mobile\traits\AuthTrait;
use app\mobile\validate\ChatValidate;
use think\Db;
use think\facade\Request;
use Tools\Responses;

class ChatController
{

    use AuthTrait;

    public function __construct()
    {
        $this->initAuthInfo();
    }


    public function lists(){


        $ChatModel = new ChatModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $ChatModel = $ChatModel->where('id','in',$value);
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $ChatModel = $ChatModel->where('status','in',$value);
                        }
                        break;
                }
            }
        }

        $ChatModel = $ChatModel->where('(create_user_id = '.self::$user_id.' OR '.'receiver_user_id = '.self::$user_id.')');

        $page_size = Request::post('page_size',env('page_size',10));
        $lists = $ChatModel
            ->with([
                'create_user'=>function($query){
                    $query->field('id,nick_name,avatar');
                },
                'receiver_user'=>function($query){
                    $query->field('id,nick_name,avatar');
                }
            ])
            ->order('updated_at DESC,id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = [];
        if($lists['data']){
            foreach ($lists['data'] as $data){
                $data['last_message'] = ChatListModel::where('chat_id','=',$data['id'])
                    ->order('id DESC')
                    ->find();
                $datas[] = $data;
            }
        }


        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


    public function contentList(){


        $ChatListModel = new ChatListModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        $chat_id = 0;
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $ChatListModel = $ChatListModel->where('id','in',$value);
                        }
                        break;
                    case 'chat_id':
                        $value = (int)$value;
                        $chat_id = $value;
                        if($value){
                            $ChatListModel = $ChatListModel->where('chat_id','=',$value);
                        }
                        break;
                    case 'id-gt':
                        if($value != -1 && $value >= 0){
                            $ChatListModel = $ChatListModel->where('id','>',(int)$value);
                        }
                        break;
                    case 'id-lt':
                        if($value != -1 && $value >= 0){
                            $ChatListModel = $ChatListModel->where('id','<',(int)$value);
                        }
                        break;
                }
            }
        }

        if($chat_id < 1){
            return Responses::data(40003, '请选择聊天');
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $orders = Request::post('orders',[['id','DESC']]);
        $orderString = '';
        if($orders && is_array($orders)){
            $orderList = [];
            foreach ($orders as $row){
                if(isset($row[0]) && isset($row[1]) && in_array($row[1],['asc','desc'])){
                    switch ($row[0]){
                        case 'id':
                            $orderList[] = 'id '.$row[1];
                            break;
                    }
                }
            }
            $orderString = implode(',',$orderList);
        }


        $lists = $ChatListModel
            ->order($orderString)
            ->paginate($page_size)
            ->toArray();

        $datas = $lists['data'];

        $chat_info = ChatModel::where('id','=',$chat_id)->find();
        if(!$chat_info){
            return Responses::data(40402, '聊天不存在');
        }

        //更新已读数量
        $date = date('Y-m-d H:i:s');
        $chat_data = [];
        if($chat_info['create_user_id'] == self::$user_id){
            $chat_data['create_read_dt'] = $date;
            $chat_data['create_total'] = 0;
            $chat_data['create_status'] = 1;
        }else{
            $chat_data['receiver_read_dt'] = $date;
            $chat_data['receiver_total'] = 0;
            $chat_data['receiver_status'] = 1;
        }
        $chat_data['updated_at'] = $date;
        ChatModel::where('id','=',$chat_id)->update($chat_data);

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));


    }


    public function detail(){

        //表单验证
        $validate = new ChatValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id',0,'intval');
        //历史聊天窗口
        $historyChat = ChatModel::where('id','=',$id)
            ->order('id DESC')
            ->find();

        if (!$historyChat) {
            throw new RequestException('查看失败：未正常打开聊天窗口', 40003);
        }

        $users = [$historyChat['create_user_id'],$historyChat['receiver_user_id']];
        if(!in_array(self::$user_id,$users)){
            throw new RequestException( '只能查看自己的聊天消息',40003);
        }

        //列表的接收者
        $receiver_user_id = self::$user_id == $historyChat['create_user_id'] ? $historyChat['receiver_user_id'] : $historyChat['create_user_id'];
        $historyChat['receiver_user'] = UserModel::where('id','=',$receiver_user_id)
            ->field('id,nick_name,phone,avatar')
            ->find();

        //更新已读数量
        $date = date('Y-m-d H:i:s');
        $chat_data = [];
        $chat_info = $historyChat;
        $chat_id = $id;
        if($chat_info['create_user_id'] == self::$user_id){
            $chat_data['create_read_dt'] = $date;
            $chat_data['create_total'] = 0;
            $chat_data['create_status'] = 1;
        }else{
            $chat_data['receiver_read_dt'] = $date;
            $chat_data['receiver_total'] = 0;
            $chat_data['receiver_status'] = 1;
        }
        $chat_data['updated_at'] = $date;
        ChatModel::where('id','=',$chat_id)->update($chat_data);

        return Responses::data(200,'success',$historyChat);

    }


    public function open(){

        //表单验证
        $validate = new ChatValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $receiver_user_id = Request::post('user_id',0,'intval');
        $receiver_user = UserModel::where('id','=',$receiver_user_id)->find();

        if(self::$user_id == $receiver_user_id){
            throw new RequestException( '不能发送给自己',40003);
        }

        //用户不存在
        if(!$receiver_user){
            throw new RequestException( '接收用户不存在',40003);
        }

        $kefu_ids = json_decode(dbConfig('site.kefu_ids','[]'),true);
        //不是发送给客服
        if(!in_array($receiver_user_id,$kefu_ids)){

            //禁止聊天
            if(in_black(self::$user_id,1002)){
                throw new RequestException('平台禁止您发起聊天，请联系客服解冻',40003);
            }


            //历史聊天窗口
            $historyChat = ChatModel::where('((create_user_id=' . self::$user_id . ' AND receiver_user_id=' . $receiver_user_id . ') OR (create_user_id=' . $receiver_user_id . ' AND receiver_user_id=' . self::$user_id . '))')
                ->order('id DESC')
                ->find();

            if ($historyChat) {
                return Responses::data(200, 'success', ['id' => $historyChat['id']]);
            }

            //活动往来
            $historyTask = TaskModel::where('((user_id=' . self::$user_id . ' AND merchant_id=' . $receiver_user_id . ') OR (user_id=' . $receiver_user_id . ' AND merchant_id=' . self::$user_id . '))')
                ->order('id DESC')
                ->find();
            if (!$historyTask) {
                throw new RequestException('没有任何活动往来', 40003);
            }

        }

        $date = date('Y-m-d H:i:s');
        $data = [];
        $data['create_user_id'] = self::$user_id;
        $data['receiver_user_id'] = $receiver_user_id;
        $data['create_read_dt'] = $date;
        $data['create_update_dt'] = $date;
        $data['receiver_read_dt'] = $date;
        $data['receiver_update_dt'] = $date;
        $data['create_total'] = 0;
        $data['receiver_total'] = 0;
        $data['create_status'] = 1;
        $data['receiver_status'] = 1;

        try{

            //写入聊天
            $chat = ChatModel::create($data);

            if(!$chat){
                throw new \Exception('创建窗口失败',50001);
            }

            return Responses::data(200, 'success',['id'=>$chat['id']]);
        }catch (\Exception $e){

            return Responses::data(50001, '申请失败');
        }

    }


    public function send(){

        //表单验证
        $validate = new ChatValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id',0,'intval');
        //历史聊天窗口
        $historyChat = ChatModel::where('id','=',$id)
            ->order('id DESC')
            ->find();

        if (!$historyChat) {
            throw new RequestException('发送失败：未正常打开聊天窗口', 40003);
        }

        $kefu_ids = json_decode(dbConfig('site.kefu_ids','[]'),true);
        //不是发送给客服
        if((!in_array($historyChat['create_user_id'],$kefu_ids)) || (!in_array($historyChat['receiver_user_id'],$kefu_ids))){
            //禁止聊天
            if(in_black(self::$user_id,1002)){
                throw new RequestException('平台禁止您发起聊天，请联系客服解冻',40003);
            }
        }

        $users = [$historyChat['create_user_id'],$historyChat['receiver_user_id']];
        if(!in_array(self::$user_id,$users)){
            throw new RequestException( '只能在自己的聊天窗口发送消息',40003);
        }

        /*
        if(self::$user_id == $historyChat['create_user_id'] && self::$user_id == $historyChat['receiver_user_id']){
            throw new RequestException( '不能发送给自己',40003);
        }
        */

        //列表的接收者
        $receiver_user_id = self::$user_id == $historyChat['create_user_id'] ? $historyChat['create_user_id'] : $historyChat['receiver_user_id'];

        $date = date('Y-m-d H:i:s');
        $chat_data = [];
        if($historyChat['create_user_id'] == self::$user_id){
            $chat_data['create_read_dt'] = $date;
            $chat_data['create_update_dt'] = $date;
            $chat_data['status'] = 1;
            $chat_data['create_total'] = 0;
            $chat_data['receiver_total'] = Db::raw('receiver_total+1');
            $chat_data['create_status'] = 1;
            $chat_data['receiver_status'] = 2;
        }else{
            $chat_data['receiver_read_dt'] = $date;
            $chat_data['receiver_update_dt'] = $date;
            $chat_data['status'] = 1;
            $chat_data['receiver_total'] = 0;
            $chat_data['create_total'] = Db::raw('receiver_total+1');
            $chat_data['receiver_status'] = 1;
            $chat_data['create_status'] = 2;
        }


        $data = [];
        $data['chat_id'] = $id;
        $data['sender_user_id'] = self::$user_id;
        $data['receiver_user_id'] = $receiver_user_id;
        $data['type'] = Request::post('type',0,'intval');
        $data['content'] = Request::post('content','','trim,strip_tags,htmlspecialchars');
        $data['status'] = 2;

        try{

            //更新聊天
            $result = ChatModel::where('id','=',$historyChat['id'])->update($chat_data);

            if(!$result){
                throw new \Exception('更新失败',50001);
            }

            $result = ChatListModel::create($data);
            if(!$result){
                throw new \Exception('更新失败',50001);
            }

            return Responses::data(200, 'success',$result);
        }catch (\Exception $e){

            return Responses::data(50001, '更新失败'.$e->getMessage(),Db::getLastSql());
        }


    }



}
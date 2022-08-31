<?php

namespace app\common\model;


use think\Model;

class ChatModel extends Model
{

    protected $table = 'chats';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    public function createUser(){
        return $this->belongsTo(UserModel::class,'create_user_id');
    }

    public function receiverUser(){
        return $this->belongsTo(UserModel::class,'receiver_user_id');
    }


}
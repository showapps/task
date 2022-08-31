<?php


namespace app\common\model;


use think\Model;

class UserFollowModel extends Model
{

    protected $table = 'user_follows';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [];


    public function user(){
        return $this->belongsTo(UserModel::class,'user_id');
    }


    public function followedUser(){
        return $this->belongsTo(UserModel::class,'followed_user_id');
    }

}
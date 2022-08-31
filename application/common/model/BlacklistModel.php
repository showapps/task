<?php


namespace app\common\model;


use think\Model;

class BlacklistModel extends Model
{

    protected $table = 'blacklists';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'authoritys'=>'array'
    ];


    public function user(){
        return $this->belongsTo(UserModel::class,'user_id');
    }

}
<?php

namespace app\common\model;


use think\Model;

class CertificationLogModel extends Model
{

    protected $table = 'certification_logs';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [];


    public function user(){
        return $this->belongsTo(UserModel::class,'user_id');
    }

}
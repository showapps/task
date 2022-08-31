<?php


namespace app\common\model;


use think\Model;

class ActivityModel extends Model
{

    protected $table = 'activitys';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [];


    public function category(){
        return $this->belongsTo(ActivityCategoryModel::class,'category_id');
    }


    public function steps(){
        return $this->hasMany(ActivityStepModel::class,'activity_id');
    }


    public function merchant(){
        return $this->belongsTo(UserModel::class,'merchant_id');
    }


    public function user(){
        return $this->belongsTo(UserModel::class,'user_id');
    }

}
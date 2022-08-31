<?php


namespace app\common\model;


use think\Model;

class TaskModel extends Model
{

    protected $table = 'tasks';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'audit_reason'=>'array',
        'recheck_reason'=>'array',
        'report_data'=>'array',
        'report_bl_data'=>'array',
        'report_reason'=>'array',
    ];


    public function category(){
        return $this->belongsTo(ActivityCategoryModel::class,'category_id');
    }


    public function steps(){
        return $this->hasMany(ActivityStepModel::class,'activity_id');
    }


    public function activity(){
        return $this->belongsTo(ActivityModel::class,'activity_id');
    }


    public function taskSteps(){
        return $this->hasMany(TaskStepModel::class,'task_id');
    }


    public function user(){
        return $this->belongsTo(UserModel::class,'user_id');
    }


    public function merchant(){
        return $this->belongsTo(UserModel::class,'merchant_id');
    }

}
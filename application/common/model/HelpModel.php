<?php


namespace app\common\model;


use think\Model;

class HelpModel extends Model
{

    protected $table = 'helps';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [];

    public function category(){
        return $this->belongsTo(HelpCategoryModel::class,'category_id');
    }

}
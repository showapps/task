<?php


namespace app\common\model;


use think\Model;

class ActivityCategoryModel extends Model
{

    protected $table = 'activity_categorys';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [];

}
<?php


namespace app\common\model;


use think\Model;

class HelpCategoryModel extends Model
{

    protected $table = 'help_categorys';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [];

}
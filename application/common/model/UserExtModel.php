<?php


namespace app\common\model;


use think\Model;

class UserExtModel extends Model
{

    protected $table = 'user_exts';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [];

}
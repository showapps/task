<?php


namespace app\common\model;


use think\Model;

class UserWechatModel extends Model
{

    protected $table = 'user_wechats';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [];

}
<?php


namespace app\common\model;


use think\Model;

class OrderModel extends Model
{

    protected $table = 'orders';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [];

}
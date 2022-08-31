<?php


namespace app\common\model;


use think\Model;

class TokenModel extends Model
{

    protected $table = 'tokens';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';


    protected $type = [
        'info'=>'array'
    ];


}
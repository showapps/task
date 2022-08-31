<?php
/**
 * FeedbackModel.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/9/29
 */

namespace app\common\model;


use think\Model;

class FeedbackModel extends Model
{

    protected $table = 'feedbacks';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'images'=>'array'
    ];


    public function user(){
        return $this->belongsTo(UserModel::class,'user_id');
    }


}
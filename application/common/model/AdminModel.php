<?php
/**
 * AdminModel.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/15
 */

namespace app\common\model;


use think\Model;

class AdminModel extends Model
{

    protected $table = 'admins';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [];

    protected $hidden = ['password'];



    public function user()
    {
        return $this->belongsTo(UserModel::class,'user_id');
    }

    public function role()
    {
        return $this->belongsTo(AdminRoleModel::class,'role_id');
    }


}
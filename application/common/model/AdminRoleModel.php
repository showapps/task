<?php
/**
 * AdminRoleModel.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/15
 */

namespace app\common\model;


use think\Model;

class AdminRoleModel extends Model
{

    protected $table = 'admin_roles';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [];

}
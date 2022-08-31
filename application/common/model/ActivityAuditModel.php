<?php
/**
 * ActivityAuditModel.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/10/13
 */

namespace app\common\model;


use think\Model;

class ActivityAuditModel extends Model
{

    protected $table = 'activity_audits';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [];


}
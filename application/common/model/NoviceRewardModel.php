<?php
/**
 * NoviceRewardModel.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/10/23
 */

namespace app\common\model;


use think\Model;

class NoviceRewardModel extends Model
{

    protected $table = 'novice_rewards';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [];

}
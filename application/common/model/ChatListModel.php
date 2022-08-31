<?php
/**
 * ChatModel.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/10/15
 */

namespace app\common\model;


use think\Model;

class ChatListModel extends Model
{

    protected $table = 'chat_lists';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
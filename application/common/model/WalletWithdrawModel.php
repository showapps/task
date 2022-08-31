<?php
/**
 * WalletWithdrawModel.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/9/30
 */

namespace app\common\model;


use think\Model;

class WalletWithdrawModel extends Model
{

    protected $table = 'wallet_withdraws';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [];


    public function user(){
        return $this->belongsTo(UserModel::class,'user_id');
    }

}
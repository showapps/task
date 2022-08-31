<?php


namespace app\common\model;


use app\common\exception\DbException;
use app\common\exception\RequestException;
use Carbon\Carbon;
use think\Db;
use think\facade\Hook;
use think\Model;

class UserModel extends Model
{

    protected $table = 'users';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [];

    protected $hidden = ['password'];



    public function ext()
    {
        return $this->hasOne(UserExtModel::class,'user_id');
    }

    public static function register($phone,$share_code = '')
    {

        $data = [];
        $data['phone'] = $phone;
        $data['password'] = password_encrypt($phone);
        $last_user_id = (int)self::order('id DESC')->value('id');
        $data['invitation_code'] = get_invitation_code($last_user_id + 1).mt_rand(111,999);
        $data['nick_name'] = '游客'.mt_rand(111,999);
        $data['user_name'] = time().mt_rand(111,999).mt_rand(111,999);
        $data['avatar'] = 'https://static.kechuang.link/task/static/images/default/user.png';
        $data['user_level'] = 1;
        $data['user_level_edate'] = Carbon::now()->addYears(2)->toDateString();


        //父级
        $parent_id = 0;
        $parent2_id = 0;
        if($share_code){
            $parent = UserModel::where('invitation_code','=',$share_code)->find();
            if ($parent){
                $parent_id = $parent['id'];
                //父父级
                $parent2_id = $parent['parent_id'] >= 1 ? $parent['parent_id'] : 0;
            }
        }

        $data['parent_id'] = $parent_id;
        $data['parent2_id'] = $parent2_id;
        Db::startTrans();
        try {

            //注册用户
            $user = self::create($data);

            if(!$user){
                throw new DbException('注册失败',50001);
            }

            $user = self::where('id','=',$user['id'])->find();
            Hook::listen('user_register',$user);

            // 提交事务
            Db::commit();
            return $user;

        }catch (DbException $e){

            // 回滚事务
            Db::rollback();
            return false;
        }

    }

    public static function mpRegister($mpInfo = [],$share_code = '')
    {

        $data = [];
        $data['phone'] = '';
        $data['password'] = password_encrypt('123456&'.mt_rand(111,999));
        $last_user_id = (int)self::order('id DESC')->value('id');
        $data['invitation_code'] = get_invitation_code($last_user_id + 1).mt_rand(111,999);
        $data['nick_name'] = '游客'.mt_rand(111,999);
        $data['user_name'] = time().mt_rand(111,999).mt_rand(111,999);
        $data['avatar'] = 'https://static.kechuang.link/task/static/images/default/user.png';
        $data['is_wechat'] = 1;
        $data['gender'] = 0;

        //父级
        $parent_id = 0;
        $parent2_id = 0;
        if($share_code){
            $parent = UserModel::where('invitation_code','=',$share_code)->find();
            if ($parent){
                $parent_id = $parent['id'];
                //父父级
                $parent2_id = $parent['parent_id'] >= 1 ? $parent['parent_id'] : 0;
            }
        }

        $data['parent_id'] = $parent_id;
        $data['parent2_id'] = $parent2_id;

        //微信微信号
        $wechat_data = [];
        $wechat_data['mp_open_id'] = $mpInfo['openid'];
        $wechat_data['miniapp_open_id'] = get_rand_union_id();
        $wechat_data['app_open_id'] = get_rand_union_id();
        $wechat_data['union_id'] = isset($mpInfo['unionid']) && $mpInfo['unionid'] ? $mpInfo['unionid'] : get_rand_union_id();
        $wechat_data['gender'] = $data['gender'];
        $wechat_data['avatar'] = $data['avatar'];

        //昵称
        if(isset($mpInfo['nickname']) && $mpInfo['nickname']){
            $wechat_data['nick_name'] = $mpInfo['nickname'];
            $data['nick_name'] = $mpInfo['nickname'];
        }

        //头像
        if(isset($mpInfo['headimgurl']) && $mpInfo['headimgurl']){
            $avatar = str_ireplace('http://','https://',$mpInfo['headimgurl']);
            $wechat_data['avatar'] = $avatar;
            $data['avatar'] = $avatar;
        }

        //性别
        if(isset($mpInfo['sex']) && $mpInfo['sex']){
            $gender = in_array($mpInfo['sex'],[1,2]) ? $mpInfo['sex'] : 0;
            $wechat_data['gender'] = $gender;
            $data['gender'] = $gender;
        }

        Db::startTrans();
        try {

            //注册用户
            $user = self::create($data);

            if(!$user){
                throw new DbException('注册失败',50001);
            }

            $wechat_data['user_id'] = $user['id'];
            UserWechatModel::create($wechat_data);

            $user = self::where('id','=',$user['id'])->find();
            Hook::listen('user_register',$user);

            // 提交事务
            Db::commit();
            return [true,$user];

        }catch (DbException $e){

            // 回滚事务
            Db::rollback();
            return [false,$e->getMessage()];
        }

    }


    public static function getBalanceTotal($user_id)
    {

        $income = (int)WalletModel::where('user_id','=',$user_id)
            ->where('type','=',1)
            ->where('status','=',100)
            ->sum('money');

        $expend = (int)WalletModel::where('user_id','=',$user_id)
            ->where('type','=',2)
            ->where('status','in',[1,100])
            ->sum('money');

        return $income - $expend;
    }


    public static function getDepositTotal($user_id)
    {

        $income = (int)DepositModel::where('user_id','=',$user_id)
            ->where('type','=',1)
            ->where('status','=',100)
            ->sum('money');

        $expend = (int)DepositModel::where('user_id','=',$user_id)
            ->where('type','=',2)
            ->where('status','in',[1,100])
            ->sum('money');

        return $income - $expend;
    }


    public static function getIncomeTotal($user_id)
    {

        return (int)CommissionModel::where('user_id','=',$user_id)
            ->where('status','=',100)
            ->sum('money');

    }


    public static function resetTotalAccount($user_id)
    {

        $data = [
            'balance'=>self::getBalanceTotal($user_id),
            'created_at'=>date('Y-m-d H:i:s'),
        ];

        //重新计算余额
        self::where('id','=',$user_id)->update($data);

        return $data;
    }


    public static function resetTotalDeposit($user_id)
    {

        $data = [
            'deposit'=>self::getDepositTotal($user_id),
            'created_at'=>date('Y-m-d H:i:s'),
        ];

        //重新计算余额
        self::where('id','=',$user_id)->update($data);

        return $data;
    }



    public static function setTotalAccountDec($user_id,$money)
    {
        self::where('id','=',$user_id)->setDec('balance',$money);
    }


    public static function resetTotalAccountFreeStock($user_id)
    {

        $data = [
            'free_stock'=>self::getFreeStockTotal($user_id),
            'created_at'=>date('Y-m-d H:i:s'),
        ];

        //重新计算余额
        self::where('id','=',$user_id)->update($data);

        return $data;
    }


    public static function resetTotalIncome($user_id)
    {

        $data = [
            'income'=>self::getIncomeTotal($user_id),
            'created_at'=>date('Y-m-d H:i:s'),
        ];

        //重新计算余额
        self::where('id','=',$user_id)->update($data);

        return $data;
    }

}
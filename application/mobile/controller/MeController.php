<?php

namespace app\mobile\controller;


use app\common\exception\DbException;
use app\common\exception\Exception;
use app\common\exception\RequestException;
use app\common\model\ActivityModel;
use app\common\model\BlacklistModel;
use app\common\model\CertificationLogModel;
use app\common\model\ChatModel;
use app\common\model\MessageModel;
use app\common\model\OrderModel;
use app\common\model\RefreshSkuModel;
use app\common\model\TaskModel;
use app\common\model\TokenModel;
use app\common\model\UserExtModel;
use app\common\model\UserFollowModel;
use app\common\model\UserModel;
use app\common\model\VipSkuModel;
use app\common\model\WalletModel;
use app\mobile\traits\ModuleTrait;
use app\mobile\traits\AuthTrait;
use app\mobile\validate\FollowValidate;
use app\mobile\validate\MeBuyRefreshValidate;
use app\mobile\validate\MeBuyVipValidate;
use app\mobile\validate\MeCertificationValidate;
use BaiduOCR\AipOcr;
use think\Db;
use think\facade\Hook;
use think\facade\Request;
use Tools\Responses;

class MeController
{

    use ModuleTrait;
    use AuthTrait;



    /**
     * 初始化
     * */
    public function __construct()
    {
        $this->initAuthInfo();
    }


    /**
     * 数据详情
     *
     * @return Responses
     * */
    public function detail()
    {

        //生成二维码
        /*
        if(!self::$user['qrcode']){
            //self::$user['qrcode'] = create_user_qrcode(self::$user['invitation_code']);

            UserModel::where('id','=',self::$user_id)->update([
               'qrcode'=>self::$user['qrcode']
            ]);

        }
        */

        $user = self::$user;
        $user['ext'] = UserExtModel::where('user_id','=',$user['id'])->find();

        //分转元
        $user['deposit'] = fen_to_float($user['deposit']);
        $user['balance'] = fen_to_float($user['balance']);
        $user['income'] = fen_to_float($user['income']);

        //处理权限
        //1001:登录;1002:聊天;1003:提现;1004:发布活动;1005:申请任务;
        $authoritys = [1001,1002,1003,1004,1005];
        //获取黑名单权限
        $blackInfo = BlacklistModel::where('user_id','=',self::$user_id)
            ->field('id,authoritys')
            ->find();

        if($blackInfo){
            $authoritys = array_diff($authoritys,$blackInfo['authoritys']);
            sort($authoritys);
        }

        $user['authoritys'] = $authoritys;

        //统计
        $user['today_income'] =  fen_to_float(WalletModel::where('user_id','=',self::$user_id)
            ->where('type','=',1)
            ->whereTime('finish_dt','>',date('Y-m-d 00:00:00'))
            ->sum('actual_amount'));

        //待审核佣金
        $user['task_pending_commission'] = fen_to_float(TaskModel::where('user_id','=',self::$user_id)
            ->whereIn('status',[2,3,5,6])
            ->sum('money'));
        $user['task_pending_commission'] = get_task_commission_calc($user['task_pending_commission'],self::$user);
        $user['system_message_total'] = (int)MessageModel::where('user_id','=',self::$user_id)
            ->where('status','=',2)
            ->count();
        $user['chat_message_total'] = (int)ChatModel::where('((create_user_id = '.self::$user_id.' && create_total > 0) OR (receiver_user_id = '.self::$user_id.' && receiver_total > 0))')
            ->count();

        $user['message_total'] = $user['system_message_total'] + $user['chat_message_total'];
        $user['activity_total'] = ActivityModel::where('merchant_id','=',self::$user_id)
            ->where('status','=',100)
            ->count();
        $user['task_total'] = TaskModel::where('user_id','=',self::$user_id)
            ->whereIn('status',[1,2,3,5,6,100])
            ->count();
        $user['fans_total'] = UserFollowModel::where('followed_user_id','=',self::$user_id)->count();
        $user['follow_total'] = UserFollowModel::where('user_id','=',self::$user_id)->count();

        return Responses::data(200, 'success',self::$user);

    }


    /**
     * 修改密码 [通过密码]
     *
     * @return Responses
     * */
    public function updatePassword()
    {

        //表单验证
        $validate = new PasswordUpdateValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $password = password_encrypt(Request::post('password','','trim'));
        $npassword = password_encrypt(Request::post('npassword','','trim'));

        if(self::$user['password'] != $password){
            throw new DbException('密码错误',40003);
        }

        try {

            //更新密码
            $result = UserModel::where('id','=',self::$user_id)->update([
                'password'=> $npassword,
                'updated_at'=> date('Y-m-d H:i:s'),
            ]);

            if(!$result){
                throw new DbException('修改失败',50001);
            }

            //销毁全部 token
            TokenModel::where('user_id','=',self::$user_id)
                ->where('guard','in',['admin','mobile'])
                ->update([
                    'end_dt'=>date('Y-m-d H:i:s'),
                    'updated_at'=>date('Y-m-d H:i:s')
                ]);

            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


    /**
     * 修改密码 [通过短信]
     *
     * @return Responses
     * */
    public function updatePasswordByPhone()
    {

        //表单验证
        $data = Request::post();

        if(!self::$user['phone']){
            throw new DbException('您还未绑定手机号',40003);
        }

        $data['phone'] = self::$user['phone'];
        $validate = new PasswordUpdateValidate();
        $vResult = $validate->scene(__FUNCTION__)->check($data);
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $npassword = password_encrypt(Request::post('npassword','','trim'));

        try {

            //更新密码
            $result = UserModel::where('id','=',self::$user_id)->update([
                'password'=> $npassword,
                'updated_at'=> date('Y-m-d H:i:s'),
            ]);

            if(!$result){
                throw new DbException('修改失败',50001);
            }

            //销毁全部 token
            TokenModel::where('user_id','=',self::$user_id)
                ->where('guard','in',['admin','mobile'])
                ->update([
                    'end_dt'=>date('Y-m-d H:i:s'),
                    'updated_at'=>date('Y-m-d H:i:s')
                ]);

            //销毁短信验证码
            //delete_sms_code(Request::post('captcha_sms.id'));
            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }



    /**
     * 修改资料 [单个字段]
     *
     * @return Responses
     * */
    public function updateField($field)
    {

        $field = strtolower($field);

        //表单验证
        $validate = new MeUpdateFieldValidate();
        $vResult = $validate->scene($field)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $data = [];
        $data['updated_at'] = date('Y-m-d H:i:s');

        switch ($field){
            case 'avatar'://头像
                $data['avatar'] = Request::post('avatar','','trim');
                break;
            case 'nick_name'://昵称
                $data['nick_name'] = Request::post('nick_name','','trim');
                break;
            case 'gender'://性别
                $data['gender'] = Request::post('gender',0,'intval');
                break;
            case 'birthday'://生日
                $data['birthday'] = Request::post('birthday','','trim');
                $data['age'] = date('Y') - date('Y',strtotime($data['birthday']));
                break;
            case 'wechat'://微信
                $data['wechat_number'] = Request::post('wechat','','trim');
                break;
            case 'phone'://手机
                $data['phone'] = Request::post('phone','','trim');
                $phoneCount = UserModel::where('phone','=',$data['phone'])
                    ->where('id','<>',self::$user_id)
                    ->count();
                if($phoneCount >= 1){
                    throw new RequestException( '手机号已被注册',40003);
                }
                break;
        }

        try {

            //更新资料
            $result = UserModel::where('id','=',self::$user_id)->update($data);

            if(!$result){
                throw new DbException('修改失败',50001);
            }

            //销毁短信验证码
            if($field == 'phone'){
                //delete_sms_code(Request::post('captcha_sms.id'));
            }

            //资料更新
            Hook::listen('user_update',UserModel::where('id','=',self::$user_id)->find());
            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }



    /**
     * 实名认证
     *
     * @return Responses
     * */
    public function certification()
    {

        set_time_limit(0);

        //表单验证
        $validate = new MeCertificationValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());

        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        if(self::$user['certification_status'] == 100){
            throw new RequestException( '您已实名，不能重复实名',40003);
        }

        $day_count = CertificationLogModel::where('user_id','=',self::$user_id)
            ->where('day','=',date('Ymd'))
            ->count();

        if($day_count >= 3){
            throw new RequestException( '您今日已提交3次实名，请等待明日',40003);
        }

        //加入日志
        CertificationLogModel::create([
            'user_id'=>self::$user_id,
            'day'=>date('Ymd'),
            'status'=>0,
        ]);

        $data = [];
        $data['id_front'] = Request::post('id_front','','trim');
        $data['id_reverse'] = Request::post('id_reverse','','trim');

        //检查身份证正面
        $front_result = check_id_crad_front($data['id_front']);
        if($front_result['code'] != 200){
            throw new DbException($front_result['message'],50001);
        }

        $data['true_name'] = $front_result['data']['name'];
        $data['id_number'] = $front_result['data']['code'];

        //检查身份证背面
        $reverse_result = check_id_crad_reverse($data['id_reverse']);
        if($reverse_result['code'] != 200){
            throw new DbException($reverse_result['message'],50001);
        }

        //通过身份证&姓名进行实名认证
        $result = check_id_crad($data['true_name'],$data['id_number']);
        if($result['code'] != 200){
            throw new DbException($result['message'],50001);
        }

        $data['certification_status'] = 100;//待审核
        $data['updated_at'] = date('Y-m-d H:i:s');

        try {

            //更新认证信息
            $result = UserExtModel::where('user_id','=',self::$user_id)->update($data);

            if(!$result){
                throw new DbException('操作失败',50001);
            }

            UserModel::where('id','=',self::$user_id)->update([
                'true_name'=>$data['true_name'],
                'certification_status'=>$data['certification_status'],
                'updated_at'=>date('Y-m-d H:i:s'),
            ]);

            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


    /**
     * 数据列表
     *
     * @return Responses
     * */
    public function vipSkuList()
    {

        $VipSkuModel = new VipSkuModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $VipSkuModel = $VipSkuModel->where('id','in',$value);
                        }
                        break;
                    case 'type':
                        $value = intval($value);
                        if($value){
                            $VipSkuModel = $VipSkuModel->where('type','=',$value);
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $VipSkuModel = $VipSkuModel->where('status','in',$value);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $VipSkuModel
            ->order('sort DESC,id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = [];
        if($lists['data']){
            foreach ($lists['data'] as $data){
                $data['original_price'] = fen_to_float($data['original_price']);
                $data['price'] = fen_to_float($data['price']);
                $datas[] = $data;
            }
        }

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


    /**
     * 数据列表
     *
     * @return Responses
     * */
    public function refreshSkuList()
    {

        $RefreshSkuModel = new RefreshSkuModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $RefreshSkuModel = $RefreshSkuModel->where('id','in',$value);
                        }
                        break;
                    case 'type':
                        $value = intval($value);
                        if($value){
                            $RefreshSkuModel = $RefreshSkuModel->where('type','=',$value);
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $RefreshSkuModel = $RefreshSkuModel->where('status','in',$value);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $RefreshSkuModel
            ->order('sort DESC,id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = [];
        if($lists['data']){
            foreach ($lists['data'] as $data){
                $data['original_price'] = fen_to_float($data['original_price']);
                $data['price'] = fen_to_float($data['price']);
                $datas[] = $data;
            }
        }

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }



    /**
     * 代理升级
     *
     * @return Responses
     * */
    public function buyVip()
    {

        //表单验证
        $validate = new MeBuyVipValidate();
        $vResult = $validate->check(Request::post());

        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $sku_id = Request::post('sku_id','','intval');
        $skuInfo = VipSkuModel::where('id','=',$sku_id)->find();
        if(!$skuInfo){
            throw new RequestException( '开通规格不存在，请刷新页面再重试',40102);
        }
        if($skuInfo['status'] != 1){
            throw new RequestException( '开通规格已下架，请刷新页面再重试',40102);
        }

        $order = [];
        $order['order_no'] = get_rand_order_no();
        $order['user_id'] = self::$user_id;
        $order['product_type'] = 22;
        $order['product_title'] = '开通会员';
        $order['product_id'] = $skuInfo['type'];
        $order['product_spec_id'] = $sku_id;
        $order['busines_id'] = $skuInfo['month'];
        $order['busines_child_id'] = $skuInfo['refresh_number'];
        $order['money'] = $skuInfo['price'];
        $order['status'] = 1;
        $order['end_dt'] = date('Y-m-d H:i:s',time() + 1800);

        //父级
        $order['parent_id'] = self::$user['parent_id'];
        $order['parent2_id'] = self::$user['parent2_id'];


        try {

            //插入订单信息
            $order = OrderModel::create($order);
            if(!$order){
                throw new DbException('开通失败:写入订单失败',50001);
            }

            return Responses::data(200, 'success',[
                'order'=>[
                    'id'=>$order['id'],
                    'money'=>fen_to_float($order['money'])
                ]
            ]);

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }



    /**
     * 购买刷新包
     *
     * @return Responses
     * */
    public function buyRefresh()
    {

        //表单验证
        $validate = new MeBuyRefreshValidate();
        $vResult = $validate->check(Request::post());

        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $sku_id = Request::post('sku_id','','intval');
        $skuInfo = RefreshSkuModel::where('id','=',$sku_id)->find();
        if(!$skuInfo){
            throw new RequestException( '开通规格不存在，请刷新页面再重试',40102);
        }
        if($skuInfo['status'] != 1){
            throw new RequestException( '开通规格已下架，请刷新页面再重试',40102);
        }

        $order = [];
        $order['order_no'] = get_rand_order_no();
        $order['user_id'] = self::$user_id;
        $order['product_type'] = 12;
        $order['product_title'] = '购买刷新包';
        $order['product_id'] = $sku_id;
        $order['busines_id'] = $skuInfo['number'];
        $order['money'] = $skuInfo['price'];
        $order['status'] = 1;
        $order['end_dt'] = date('Y-m-d H:i:s',time() + 1800);

        //父级
        $order['parent_id'] = self::$user['parent_id'];
        $order['parent2_id'] = self::$user['parent2_id'];


        try {

            //插入订单信息
            $order = OrderModel::create($order);
            if(!$order){
                throw new DbException('开通失败:写入订单失败',50001);
            }

            return Responses::data(200, 'success',[
                'order'=>[
                    'id'=>$order['id'],
                    'money'=>fen_to_float($order['money'])
                ]
            ]);

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


    /**
     * 关注用户
     *
     * @return Responses
     * */
    public function follow()
    {

        //表单验证
        $validate = new FollowValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $followed_user_id = Request::post('followed_user_id',0,'intval');
        $userCount = UserModel::where('id','=',$followed_user_id)->count();
        if($userCount < 1){
            throw new RequestException( '关注用户不存在',40003);
        }

        if($followed_user_id == self::$user_id){
            throw new RequestException( '不能关注自己',40003);
        }

        //已关注
        $followCount = UserFollowModel::where('user_id','=',self::$user_id)
            ->where('followed_user_id','=',$followed_user_id)
            ->count();

        if($followCount > 0){
            throw new RequestException( '已关注，不能重复关注',40003);
        }

        $data = [];
        $data['user_id'] = self::$user_id;
        $data['followed_user_id'] = $followed_user_id;

        try{

            $result = UserFollowModel::create($data);
            if(!$result){
                throw new DbException('关注失败',50001);
            }

            return Responses::data(200, 'success',[
                'id'=>$result['id'],
            ]);

        }catch (Exception $e){

            return Responses::data(50002, '关注失败');

        }


    }


    /**
     * 取关用户
     *
     * @return Responses
     * */
    public function unFollow()
    {

        //表单验证
        $validate = new FollowValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $followed_user_id = Request::post('followed_user_id',0,'intval');

        try{

            //取关
            UserFollowModel::where('user_id','=',self::$user_id)
                ->where('followed_user_id','=',$followed_user_id)
                ->delete();

            return Responses::data(200, 'success');

        }catch (Exception $e){

            return Responses::data(50002, '取关失败');

        }


    }



}
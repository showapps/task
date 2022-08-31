<?php

namespace app\admin\controller;


use app\admin\traits\AuthTrait;
use app\common\exception\DbException;
use app\common\exception\RequestException;
use app\common\model\MessageModel;
use app\common\model\UserModel;
use app\common\model\UserWechatModel;
use app\common\model\WalletModel;
use app\common\model\WalletWithdrawModel;
use app\admin\validate\WalletWithdrawValidate;
use think\Db;
use think\facade\Hook;
use think\facade\Request;
use think\facade\View;
use Tools\Responses;
use Yansongda\Pay\Pay;

class WalletWithdrawController
{

    use AuthTrait;

    protected $directory = 'wallet_withdraw';

    public function __construct()
    {
        $this->initAuthInfo();
    }





    public function index()
    {
        return view($this->directory . '/index');
    }




    /**
     * 数据列表
     *
     * @return Responses
     * */
    public function lists()
    {

        $WalletWithdrawModel = new WalletWithdrawModel();

        //处理过滤
        $filters = Request::post('filters',[]);

        //默认状态
        if(!isset($filters['status'])){
            $filters['status'] = 0;
        }

        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $WalletWithdrawModel = $WalletWithdrawModel->where('id','in',$value);
                        }
                        break;
                    case 'status':
                        $value = intval($value);
                        if($value){
                            $WalletWithdrawModel = $WalletWithdrawModel->where('status','=',$value);
                        }
                        break;
                    case 'search_text':
                        $value = htmlspecialchars(strip_tags(trim($value)));
                        if($value){
                            $user_ids = UserModel::where("(true_name='{$value}' OR user_name='{$value}' OR nick_name='{$value}' OR phone='{$value}' OR email='{$value}')")->column('id','id');
                            if($user_ids){
                                if(is_array($user_ids)){
                                    $WalletWithdrawModel = $WalletWithdrawModel->where('user_id','in',$user_ids);
                                }else{
                                    $WalletWithdrawModel = $WalletWithdrawModel->where('user_id','=',$user_ids);
                                }
                            }else{
                                $WalletWithdrawModel = $WalletWithdrawModel->where('user_id','=','0');
                            }
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page.size',10));

        $lists = $WalletWithdrawModel
            ->with('user')
            ->order('id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = [];
        if ($lists['data']){
            foreach ($lists['data'] as $data){
                $data['money'] =  fen_to_float($data['money']);
                $data['service_charge_rate'] =  hao_to_float($data['service_charge_rate']);
                $data['actual_amount'] =  fen_to_float($data['actual_amount']);
                $data['service_charge'] =  fen_to_float($data['service_charge']);
                $datas[] = $data;
            }
        }


        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


    /**
     * 数据详情
     *
     * @return Responses
     * */
    public function detail()
    {

        //表单验证
        $validate = new WalletWithdrawValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::param());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::param('id');
        $info = WalletWithdrawModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '提现不存在',40401);
        }

        //整形 转 浮点型
        $info['money'] =  fen_to_float($info['money']);
        $info['service_charge_rate'] =  hao_to_float($info['service_charge_rate']);
        $info['actual_amount'] =  fen_to_float($info['actual_amount']);
        $info['service_charge'] =  fen_to_float($info['service_charge']);

        $data = (string)View::fetch($this->directory . '/detail',compact('info'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 审核模板
     *
     * @return Responses
     * */
    public function auditView()
    {

        //表单验证
        $validate = new WalletWithdrawValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = WalletWithdrawModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '提现不存在',40401 );
        }

        $data = (string)View::fetch($this->directory . '/audit',compact('info'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 审核提现
     *
     * @return Responses
     * */
    public function audit()
    {

        //表单验证
        $validate = new WalletWithdrawValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = WalletWithdrawModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '提现不存在',40401 );
        }

        if($info['status'] != 1){
            throw new RequestException( '只能申请待审核的提现',40401 );
        }

        $data = [];
        $data['status'] = Request::post('status',2,'intval');

        if($data['status'] == 100){
            //计算手续费&实际到账金额
            $data['finish_dt'] = date('Y-m-d H:i:s');
        }else if($data['status'] == 2){
            //$data['reasons'] = Request::post('reasons','','trim,strip_tags,htmlspecialchars');
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        //审核驳回
        if($data['status'] == 2){

            Db::startTrans();
            try {

                $result = WalletWithdrawModel::where('id','=',$id)->update($data);
                if(!$result){
                    throw new DbException('审核失败',50001);
                }

                //账户变动
                $wallet_data = [];
                $wallet_data['status'] = $data['status'];
                if($data['status'] == 100){
                    $wallet_data['finish_dt'] = $data['finish_dt'];
                    $wallet_data['updated_at'] = $data['updated_at'];
                }

                WalletModel::where('id','=',$info['wallet_id'])->update($wallet_data);
                //重新计算余额
                UserModel::resetTotalAccount($info['user_id']);

                MessageModel::create([
                    'user_id'=>$info['user_id'],
                    'category'=>2003,
                    'content'=>'很抱歉，您的申请的'.fen_to_float($info['money']).'元已被驳回！',
                    'link'=>[],
                    'status'=>2,
                ]);

                Db::commit();
                return Responses::data(200, 'success');

            }catch (DbException $e){
                Db::rollback();
                return Responses::data(50001, $e->getMessage());
            }


        }elseif ($data['status'] == 100 && $info['account_type'] == 1){

        }elseif ($data['status'] == 100 && $info['account_type'] == 2){
            return $this->wechat_balance_withdraw_check($info,$data);
        }

    }




    protected function wechat_balance_withdraw_check($info,$data){

        $id = $info['id'];

        //初始化配置
        $config = dbConfig('wechat_payment',[]);
        if((!$config) || (!isset($config['key'])) || (!$config['key'])){
            throw new RequestException( '未开启微信支付',40401);
        }

        $config['app_id'] = $config['mp_app_id'];
        unset($config['mp_app_id']);
        unset($config['switch']);

        $config['notify_url'] = url('/api/wechat/withdraw/notify',[],true,true);
        $config['cert_client'] = env('root_path').'data/wxpay/cert/cert_client.pem';
        $config['cert_key'] = env('root_path').'data/wxpay/cert/cert_key.pem';
        $config['log'] = [ // optional
            'file' => env('runtime_path').'log/wechat.log',
            'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
            'type' => 'single', // optional, 可选 daily.
            'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
        ];

        //$cert_client = file_get_contents($config['cert_client']);
        //$cert_key = file_get_contents($config['cert_key']);

        //return Responses::data(50001, '审核失败',[$config,$cert_client,$cert_key]);

        try{

            $pay = Pay::wechat($config);

            $open_id = UserWechatModel::where('user_id','=',$info['user_id'])->value('mp_open_id');
            $order = [
                'partner_trade_no' => $info['trade_no'],              //商户订单号
                'openid' => $open_id,                        //收款人的openid
                //'openid' => 'oJ_Oo5QhbFTC8sWwU5fIeDQ0nLac',        //收款人的openid
                'check_name' => 'NO_CHECK',            //NO_CHECK：不校验真实姓名\FORCE_CHECK：强校验真实姓名
                //'re_user_name'=>'张三',              //check_name为 FORCE_CHECK 校验实名的时候必须提交
                'amount' => 100,                       //企业付款金额，单位为分
                //'amount' => $data['actual_amount'],   //企业付款金额，单位为分
                'desc' => '帐户提现',                  //付款说明
                'spbill_create_ip' => Request::ip(),  //发起交易的IP地址
            ];

            $result = $pay->transfer($order);

            //支付成功
            if($result){

                $result = WalletWithdrawModel::where('id','=',$id)->update($data);
                if(!$result){
                    throw new DbException('审核失败',50001);
                }

                //账户变动
                $wallet_data = [];
                $wallet_data['status'] = $data['status'];
                if($data['status'] == 100){
                    $wallet_data['finish_dt'] = $data['finish_dt'];
                    $wallet_data['updated_at'] = $data['updated_at'];
                }

                WalletModel::where('id','=',$info['wallet_id'])->update($wallet_data);

                //提现完成
                $user = UserModel::where('id','=',$info['user_id'])->find();
                Hook::listen('withdraw_finish',$user);

                MessageModel::create([
                    'user_id'=>$info['user_id'],
                    'category'=>2003,
                    'content'=>'恭喜您，您申请的'.fen_to_float($info['money']).'元现金已到账，请注意查收',
                    'link'=>[],
                    'status'=>2,
                ]);

                return Responses::data(200, 'success');

            }

            return Responses::data(50001, '审核失败');

        }catch (\Exception $e){
            return Responses::data(50001, '审核失败:'.$e->getMessage());
        }



    }


}
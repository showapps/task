<?php
/**
 * HomeController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/8
 */

namespace app\admin\controller;


use app\admin\traits\AuthTrait;
use app\common\model\OrderModel;
use app\common\model\UserModel;
use Tools\Responses;

class HomeController
{
    use AuthTrait;
    protected $directory = 'home';


    public function __construct()
    {
        $this->initAuthInfo();
    }

    public function index()
    {
        $me = self::$user;
        return view($this->directory . '/index',compact('me'));
    }

    public function welcome()
    {
        return view($this->directory . '/welcome');
    }

    public function welcomeDetail()
    {

        $datas = [];
        $datas['totals'] = [];
        $datas['totals']['admin'] = UserModel::where('is_admin','=',100)->count();
        $datas['totals']['user'] = UserModel::count();
        $datas['totals']['merchant_level'] = UserModel::where('merchant_level','=',1)->count();
        $datas['totals']['user_level'] = UserModel::where('user_level','=',1)->count();


        //近七日交易流水
        $datas['order'] = [];
        $datas['order']['datas'] = [];
        $datas['order']['dates'] = [];
        $start_time = strtotime(date('Y-m-d',strtotime('-7day')));
        for ($i=0;$i<=>7;$i++){
            $time = $start_time + (86400*$i);
            $date = date('Ymd',$time);
            $datas['order']['dates'][] = date('m月d日',$time);

            //余额支付
            $datas['order']['datas']['wallet'][] = fen_to_float(OrderModel::where('payment_type','=',1)
                ->where('status','in',[100,101,102])
                ->where('(date_format(finish_dt,\'%Y%m%d\') = '.$date.')')
                ->sum('money'));

            //微信支付
            $datas['order']['datas']['wechat'][] = fen_to_float(OrderModel::where('payment_type','=',2)
                ->where('status','in',[100,101,102])
                ->where('(date_format(finish_dt,\'%Y%m%d\') = '.$date.')')
                ->sum('money'));

        }

        return Responses::data(200,'success',$datas);
    }
}
<?php

namespace app\console\controller;


use app\common\model\UserModel;
use Tools\Responses;

class UserController
{



    public function end_user_level(){

        set_time_limit(0);

        //关闭过期会员
        UserModel::where('user_level','=',1)
            ->where('date_format(user_level_edate,\'%Y%m%d\') < '.date('Ymd'))
            ->update([
                'user_level'=>0,
                'user_level_edate'=>NULL,
            ]);

        return Responses::data(200,'success');
    }



    public function end_merchant_level(){

        set_time_limit(0);

        //关闭过期会员
        UserModel::where('merchant_level','=',1)
            ->where('date_format(merchant_level_edate,\'%Y%m%d\') < '.date('Ymd'))
            ->update([
                'merchant_level'=>0,
                'merchant_level_edate'=>NULL,
            ]);

        return Responses::data(200,'success');
    }

}
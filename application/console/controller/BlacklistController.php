<?php

namespace app\console\controller;


use app\common\model\UserModel;
use Tools\Responses;

class BlacklistController
{



    public function end(){

        set_time_limit(0);

        //关闭过期黑名单
        UserModel::where('status','=',1)
            ->where('date_format(end_dt,\'%Y%m%d\') < '.date('Ymd'))
            ->delete();

        return Responses::data(200,'success');
    }

}
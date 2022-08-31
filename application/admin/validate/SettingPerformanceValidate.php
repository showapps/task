<?php

namespace app\admin\validate;


use think\Validate;

class SettingPerformanceValidate extends Validate
{

    protected $rule = [
        'district_agency_commission' => ['require','number','between:0,100'],
        'big_agency_commission' => ['require','number','between:0,100'],
    ];

    protected $message = [
        'district_agency_commission.require' => '请填写区域代理业绩比例',
        'district_agency_commission.number' => '请填写区域代理业绩比例 0 ~ 100 之间的整数',
        'district_agency_commission.between' => '请填写区域代理业绩比例 0 ~ 100 之间的整数',
        'big_agency_commission.require' => '请填写大区代理业绩比例',
        'big_agency_commission.number' => '请填写大区代理业绩比例 0 ~ 100 之间的整数',
        'big_agency_commission.between' => '请填写大区代理业绩比例 0 ~ 100 之间的整数',
    ];

}
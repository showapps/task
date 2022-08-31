<?php


namespace app\admin\validate;


use think\Validate;

class SettingCommissionValidate extends Validate
{


    protected $rule = [
        'commission_spread_rate' => ['require','between:0,100'],
        'commission_spread2_rate' => ['require','between:0,100'],
        'commission_task_rate' => ['require','between:0,100'],
        'commission_task_vip_rate' => ['require','between:0,100'],
        'commission_buy_merchant_level_rate' => ['require','between:0,100'],
        'commission_buy_refresh_rate' => ['require','between:0,100'],
        'commission_activity_rec_rate' => ['require','between:0,100'],
    ];

    protected $message = [
        'commission_task_rate.require' => '普通用户任务佣金不能为空',
        'commission_task_rate.between' => '普通用户任务佣金 0 ~ 100 %之间',
        'commission_task_vip_rate.require' => 'VIP用户任务佣金不能为空',
        'commission_task_vip_rate.between' => 'VIP用户任务佣金 0 ~ 100 %之间',
        'commission_spread_rate.require' => '任务分佣(一级)不能为空',
        'commission_spread_rate.between' => '任务分佣(一级) 0 ~ 100 %之间',
        'commission_spread2_rate.require' => '任务分佣(二级)不能为空',
        'commission_spread2_rate.between' => '任务分佣(二级) 0 ~ 100 %之间',
        'commission_buy_merchant_level_rate.require' => '开通超级商人比例不能为空',
        'commission_buy_merchant_level_rate.between' => '开通超级商人比例 0 ~ 100 %之间',
        'commission_buy_refresh_rate.require' => '购买刷新数量不能为空',
        'commission_buy_refresh_rate.between' => '购买刷新数量 0 ~ 100 %之间',
        'commission_activity_rec_rate.require' => '活动上推荐比例不能为空',
        'commission_activity_rec_rate.between' => '活动上推荐比例 0 ~ 100 %之间',
    ];

}
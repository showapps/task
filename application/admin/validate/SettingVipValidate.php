<?php

namespace app\admin\validate;


use think\Validate;

class SettingVipValidate extends Validate
{

    protected $rule = [
        'activity_rec_sc_rate' => ['require','egt:0.01','elt:100'],
        'activity_rec_merchant_sc_rate' => ['require','egt:0.01','elt:100'],
        'vip_user_vip_threshold' => ['require','egt:0','elt:100'],
        'activity_rec_user_sc_rate' => ['require','egt:0.01','elt:100'],
        'withdraw_sc_rate' => ['require','egt:0.01','elt:100'],
        'withdraw_user_sc_rate' => ['require','egt:0.01','elt:100'],
        'withdraw_merchant_sc_rate' => ['require','egt:0.01','elt:100'],
    ];

    protected $message = [
        'commission_spread_rate.require' => '一级分佣（通用）必填',
        'commission_spread_rate.egt' => '一级分佣（通用）不能小于0.01%',
        'commission_spread_rate.elt' => '一级分佣（通用）不能大于于100%',
        'commission_spread2_rate.require' => '二级分佣（通用）必填',
        'commission_spread2_rate.egt' => '二级分佣（通用）不能小于0.01%',
        'commission_spread2_rate.elt' => '二级分佣（通用）不能大于于100%',
        'commission_task_rate.require' => '任务佣金（普通）必填',
        'commission_task_rate.egt' => '任务佣金（普通）不能小于0.01%',
        'commission_task_rate.elt' => '任务佣金（普通）不能大于于100%',
        'commission_task_vip_rate.require' => '任务佣金（VIP）必填',
        'commission_task_vip_rate.egt' => '任务佣金（VIP）不能小于0.01%',
        'commission_task_vip_rate.elt' => '任务佣金（VIP）不能大于于100%',
        'activity_rec_sc_rate.require' => '推荐手续费（普通）必填',
        'activity_rec_sc_rate.egt' => '推荐手续费（普通）不能小于0.01%',
        'activity_rec_sc_rate.elt' => '推荐手续费（普通）不能大于于100%',
        'activity_rec_merchant_sc_rate.require' => '推荐手续费（超级商人）必填',
        'activity_rec_merchant_sc_rate.egt' => '推荐手续费（超级商人）不能小于0.01%',
        'activity_rec_merchant_sc_rate.elt' => '推荐手续费（超级商人）不能大于于100%',
        'activity_rec_user_sc_rate.require' => '推荐手续费（喵达人）必填',
        'activity_rec_user_sc_rate.egt' => '推荐手续费（喵达人）不能小于0.01%',
        'activity_rec_user_sc_rate.elt' => '推荐手续费（喵达人）不能大于于100%',
        'withdraw_sc_rate.require' => '提现手续费（普通）必填',
        'withdraw_sc_rate.egt' => '提现手续费（普通）不能小于0.01%',
        'withdraw_sc_rate.elt' => '提现手续费（普通）不能大于于100%',
        'vip_user_vip_threshold.require' => '喵达人升级门槛必填',
        'vip_user_vip_threshold.egt' => '喵达人升级门槛不能小于0',
        'vip_user_vip_threshold.elt' => '喵达人升级门槛不能大于100',
        'withdraw_user_sc_rate.require' => '提现手续费（喵达人）必填',
        'withdraw_user_sc_rate.egt' => '提现手续费（喵达人）不能小于0.01%',
        'withdraw_user_sc_rate.elt' => '提现手续费（喵达人）不能大于于100%',
        'withdraw_merchant_sc_rate.require' => '提现手续费（超级商人）必填',
        'withdraw_merchant_sc_rate.egt' => '提现手续费（超级商人）不能小于0.01%',
        'withdraw_merchant_sc_rate.elt' => '提现手续费（超级商人）不能大于于100%',
    ];

}

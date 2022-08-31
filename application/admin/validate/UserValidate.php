<?php

namespace app\admin\validate;


use think\Validate;

class UserValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'ids' => ['require', 'array'],
        'title' => ['require'],
        'status' => ['require','in:1,2'],
        'certification_status' => ['require','in:3,100'],
        'reasons' => ['length:1,150'],
        'user_level' => ['require','in:0,1','checkUserLevel'],
        'merchant_level' => ['require','in:0,1','checkMerchantLevel'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'ids.require' => '请选择数据',
        'ids.array' => '请选择数据',
        'title.require' => '标题必填',
        'status.require' => '状态必须选择',
        'status.in' => '无效的状态值',
        'certification_status.require' => '认证状态必须选择',
        'certification_status.in' => '无效的认证状态值',
        'reasons.require' => '备注信息必填',
        'reasons.length' => '备注信息长度150字以内',
        'user_level.require' => '会员等级必须选择',
        'user_level.in' => '无效的会员等级值',
        'merchant_level.require' => '商家会员等级必须选择',
        'merchant_level.in' => '无效的商家会员等级值',
    ];


    protected $scene = [
        'detail' => ['id'],
        'create' => ['title'],
        'update' => ['id', 'title'],
        'switch' => ['id','status'],
        'certification_audit_view' => ['id'],
        'certification_audit' => ['id','certification_status','reasons'],
        'vip_update_view' => ['id'],
        'vip_update' => ['id','user_level','merchant_level'],
        'balance_update_view' => ['id'],
        'deposit_update_view' => ['id'],
    ];



    // 检查会员
    protected function checkUserLevel($value,$rule,$data=[])
    {

        if($value == 1){
            if((!isset($data['user_level_edate'])) || (!$data['user_level_edate'])){
                return '请选择会员的到期时间';
            }

            $end_time = strtotime($data['user_level_edate']);
            if($end_time <= strtotime(date('Y-m-d'))){
                return '会员的到期时间必须大于今日';
            }
        }

        return true;
    }



    // 检查商家会员
    protected function checkMerchantLevel($value,$rule,$data=[])
    {

        if($value == 1){
            if((!isset($data['merchant_level_edate'])) || (!$data['merchant_level_edate'])){
                return '请选择商家会员的到期时间';
            }

            $end_time = strtotime($data['merchant_level_edate']);
            if($end_time <= strtotime(date('Y-m-d'))){
                return '商家会员的到期时间必须大于今日';
            }
        }

        return true;
    }

}
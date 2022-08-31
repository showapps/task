<?php

namespace app\admin\validate;


use think\Validate;

class SettingFriendAwardValidate extends Validate
{

    protected $rule = [
        'friend_award_task_finish_1' => ['require', 'between:0,999999'],
        'friend_award_withdraw_1' => ['require', 'between:0,999999'],
        'friend_award_withdraw_2' => ['require', 'between:0,999999'],
        'friend_award_withdraw_3' => ['require', 'between:0,999999'],
        'friend_award_withdraw_4' => ['require', 'between:0,999999'],
        'friend_award_withdraw_5' => ['require', 'between:0,999999'],
        'friend_award_withdraw_6' => ['require', 'between:0,999999'],
    ];

    protected $message = [
        'friend_award_task_finish_1.require' => '请填写首次完成任务奖励金额',
        'friend_award_task_finish_1.between' => '首次完成任务奖励金额 0 ~ 999999 之间',
        'friend_award_withdraw_1.require' => '请填写首次完成提现奖励金额',
        'friend_award_withdraw_1.between' => '首次完成提现奖励金额 0 ~ 999999 之间',
        'friend_award_withdraw_2.require' => '请填写第2次完成提现奖励金额',
        'friend_award_withdraw_2.between' => '第2次完成提现奖励金额 0 ~ 999999 之间',
        'friend_award_withdraw_3.require' => '请填写第3次完成提现奖励金额',
        'friend_award_withdraw_3.between' => '第3次完成提现奖励金额 0 ~ 999999 之间',
        'friend_award_withdraw_4.require' => '请填写第4次完成提现奖励金额',
        'friend_award_withdraw_4.between' => '第4次完成提现奖励金额 0 ~ 999999 之间',
        'friend_award_withdraw_5.require' => '请填写第5次完成提现奖励金额',
        'friend_award_withdraw_5.between' => '第5次完成提现奖励金额 0 ~ 999999 之间',
        'friend_award_withdraw_6.require' => '请填写第6次完成提现奖励金额',
        'friend_award_withdraw_6.between' => '第6次完成提现奖励金额 0 ~ 999999 之间',
    ];
}
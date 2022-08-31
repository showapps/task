<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用行为扩展定义文件
return [
    // 应用初始化
    'app_init'     => [],
    // 应用开始
    'app_begin'    => [],
    // 模块初始化
    'module_init'  => [],
    // 操作开始执行
    'action_begin' => [],
    // 视图内容过滤
    'view_filter'  => [],
    // 日志写入
    'log_write'    => [],
    // 应用结束
    'app_end'      => [],
    //注册
    'user_register'      => [
        \app\common\behavior\UserRegisterBehavior::class,
    ],
    //用戶資料更新
    'user_update'      => [

    ],
    //广告更新
    'ad_update'      => [

    ],
    //任务完成
    'task_finish'      => [
        \app\common\behavior\TaskFinishBehavior::class,
    ],
    //购买超级商人
    'buy_merchant_level_finish'      => [
        \app\common\behavior\BuyMerchantLevelFinishBehavior::class,
    ],
    //购买刷新完成
    'buy_refresh_finish'      => [
        \app\common\behavior\BuyRefreshFinishBehavior::class,
    ],
    //活动上推荐
    'activity_rec_finish'      => [
        \app\common\behavior\ActivityRecFinishBehavior::class,
    ],
    //提现完成
    'withdraw_finish'      => [
        \app\common\behavior\WithdrawFinishBehavior::class,
    ],
];

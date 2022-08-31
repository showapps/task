<?php

namespace app\admin\validate;


use think\Validate;

class SettingRankingValidate extends Validate
{

    protected $rule = [
        'ranking_tasks' => ['require','array', 'checkTasks'],
        'ranking_spreads' => ['require', 'array','checkSpreads'],
    ];

    protected $message = [
        'ranking_tasks.require' => '任务榜最少配置1项！',
        'ranking_tasks.array' => '任务榜最少配置1项！',
        'ranking_spreads.require' => '推广榜最少配置1项！',
        'ranking_spreads.array' => '推广榜最少配置1项！',
    ];

    // 检查 task
    protected function checkTasks($value,$rule,$data=[])
    {

        if((!is_array($value)) || count($value) < 1){
            return '任务榜最少配置1项';
        }

        if(count($value) > 50){
            return '任务榜最多配置50项';
        }

        return true;
    }

    // 检查 spread
    protected function checkSpreads($value,$rule,$data=[])
    {

        if((!is_array($value)) || count($value) < 1){
            return '任务榜最少配置1项';
        }

        if(count($value) > 50){
            return '任务榜最多配置50项';
        }

        return true;
    }
}
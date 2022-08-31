<?php

namespace app\admin\validate;


use think\Validate;

class SettingNoviceRewardValidate extends Validate
{

    protected $rule = [
        'datas' => ['checkDatas'],
    ];

    protected $message = [
    ];

    // 检查 datas
    protected function checkDatas($value,$rule,$data=[])
    {
        if(is_array($value) && count($value) > 0){
            foreach ($value as $index=>$item){

                $indexTitle = $index + 1;
                if((!$item['title']) || $item['title'] == ''){
                    return '第'.$indexTitle.'项必须设置标题！';
                }

                if((!$item['number']) || $item['number'] == ''){
                    return '第'.$indexTitle.'项必须输入任务数量！';
                }

                if($item['number'] < 0 || $item['number'] > 999999){
                    return '第'.$indexTitle.'项任务数量必须在 0 ~ 999999 之间！';
                }

                if((!$item['award']) || $item['award'] == ''){
                    return '第'.$indexTitle.'项必须输入奖励金额！';
                }

                if($item['award'] < 0 || $item['award'] > 999999){
                    return '第'.$indexTitle.'项奖励金额必须在 0 ~ 999999 之间！';
                }
                
            }
        }
        
        return true;
    }
}
<?php

namespace app\admin\validate;


use think\Validate;

class SettingRefreshNumberValidate extends Validate
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
                    return '第'.$indexTitle.'项必须输入标题！';
                }

                if((!$item['number']) || $item['number'] == ''){
                    return '第'.$indexTitle.'项必须输入刷新数量！';
                }

                if($item['number'] < 1 || $item['number'] > 999999){
                    return '第'.$indexTitle.'项刷新数量必须在 1 ~ 999999 之间！';
                }

                if((!$item['original_price']) || $item['original_price'] == ''){
                    return '第'.$indexTitle.'项必须输入原价！';
                }

                if($item['original_price'] < 0.01 || $item['original_price'] > 999999){
                    return '第'.$indexTitle.'项原价必须在 0.01 ~ 999999 之间！';
                }

                if((!$item['price']) || $item['price'] == ''){
                    return '第'.$indexTitle.'项必须输入现价！';
                }

                if($item['price'] < 0.01 || $item['price'] > 999999){
                    return '第'.$indexTitle.'项现价必须在 0.01 ~ 999999 之间！';
                }

            }
        }

        return true;
    }
}
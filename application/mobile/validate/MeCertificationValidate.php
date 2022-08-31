<?php

namespace app\mobile\validate;

use think\Validate;

class MeCertificationValidate extends Validate
{

    protected $rule =   [
        //'true_name'  => ['require','length:1,10'],
        //'id_number'  => ['require','checkIdNumber'],
        'id_front'  => ['require','url'],
        'id_reverse'  => ['require','url'],
    ];

    protected $message  =   [
        'true_name.require' => '姓名必填',
        'true_name.length' => '姓名最大长度 10 位',
        'id_number.require' => '身份证号码必填',
        'id_front.require' => '身份证正面照必须上传',
        'id_front.url' => '身份证正面照格式错误',
        'id_reverse.require' => '身份证背面照必须上传',
        'id_reverse.url' => '身份证背面照格式错误',
    ];



    // 检查身份证号码
    protected function checkIdNumber($value,$rule,$data=[])
    {

        $length = strlen($value);
        if($length != 15 && $length != 18){
            return '身份证号码只支持15和18位长度';
        }

        return true;
    }


}
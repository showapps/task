<?php
/**
 * CompanyJobValidate.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/15
 */

namespace app\admin\validate;


use think\Validate;

class DistrictAgencyValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'status' => ['require','in:3,100','checkStatus'],
        'region' => ['require'],
        'reasons' => ['length:1,150'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'status.require' => '状态必须选择',
        'status.in' => '无效的状态值',
        'reasons.length' => '备注信息150字内',
    ];


    protected $scene = [
        'detail' => ['id'],
        'auditView' => ['id'],
        'audit' => ['id','status','reasons'],
    ];


    // 检查状态
    protected function checkStatus($value,$rule,$data=[])
    {

        if($value == 100){
            if((!isset($data['region'])) || (!$data['region'])){
                return '请选择代理地区';
            }
        }elseif($value == 3){
            if((!isset($data['reasons'])) || (!$data['reasons'])){
                return '请填写备注信息';
            }
        }

        return true;
    }

}
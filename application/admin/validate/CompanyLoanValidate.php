<?php
/**
 * CompanyJobValidate.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/15
 */

namespace app\admin\validate;


use think\Validate;

class CompanyLoanValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'ids' => ['require', 'array'],
        'company_id' => ['require', 'number'],
        'money' => ['require', 'number'],
        'status' => ['require','in:2,100','checkStatus'],
        'district' => ['require', 'number'],
        'reasons' => ['length:1,150'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'ids.require' => '请选择数据',
        'ids.array' => '请选择数据',
        'company_id.require' => '请选择企业',
        'company_id.number' => '选择的企业数据无效',
        'money.require' => '请输入贷款金额',
        'money.number' => '请输入数字的贷款金额',
        'status.require' => '状态必须选择',
        'status.in' => '无效的状态值',
        'district.require' => '请选择申请地区',
        'district.number' => '选择的申请地区无效',
        'reasons.require' => '备注字段必填',
        'reasons.length' => '备注信息150字内',
    ];


    // 检查状态
    protected function checkStatus($value,$rule,$data=[])
    {

        if($value == 2){
            if((!isset($data['reasons'])) || (!$data['reasons'])){
                return '请填写备注信息';
            }
        }

        return true;
    }

    protected $scene = [
        'detail' => ['id'],
        'auditView' => ['id'],
        'audit' => ['id','status','reasons'],
        'switch' => ['id','status'],
        'create' => ['company_id','money','district','status'],
    ];

}
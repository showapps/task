<?php
/**
 * CompanyJobValidate.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/15
 */

namespace app\admin\validate;


use think\Validate;

class CompanyJobValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'ids' => ['require', 'array'],
        'status' => ['require','in:3,2,4,100,101','checkStatus'],
        'reasons' => ['length:1,150'],
        'company_id' => ['require', 'number'],
        'number' => ['require', 'number'],
        'district' => ['require', 'number'],
        'position' => ['require', 'number'],
        'tags' => ['array'],
        'content' => ['require', 'length:1,500'],
        'degree'  => ['require','in:0,10,11,12,13,14,15,16'],
        'working_year'  => ['require','in:0,1,2,3,4,5'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'ids.require' => '请选择数据',
        'ids.array' => '请选择数据',
        'company_id.require' => '请选择企业',
        'company_id.number' => '选择的企业数据无效',
        'number.require' => '请输入招聘人数',
        'number.number' => '请输入数字的招聘人数',
        'status.require' => '状态必须选择',
        'status.in' => '无效的状态值',
        'reasons.require' => '备注字段必填',
        'reasons.length' => '备注信息150字内',
        'district.require' => '请选择申请地区',
        'district.number' => '选择的申请地区无效',
        'position.require' => '请选择招聘岗位',
        'position.number' => '选择的招聘岗位无效',
        'tags.array' => '选择的技能要求无效',
        'content.require' => '岗位职责必填',
        'content.length' => '岗位职责500字内',
        'degree.require' => '学历要求必须选择',
        'degree.in' => '无效的学历要求',
        'working_year.require' => '工作年限必须选择',
        'working_year.in' => '无效的工作年限',
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
        'create' => ['company_id','district','position','number','tags','content','status','degree','working_year'],
    ];

}
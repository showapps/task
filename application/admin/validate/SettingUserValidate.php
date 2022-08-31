<?php
/**
 * SettingUserValidate.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/25
 */

namespace app\admin\validate;


use think\Validate;

class SettingUserValidate extends Validate
{

    protected $rule = [
        'agency_first_money' => ['require','egt:0.01'],
        'agency_high_money' => ['require','egt:0.01','checkAgencyHighMoney'],
        'big_agency_register' => ['require','in:1,2'],
        'big_agency_money' => ['checkBigAgencyMoney'],
        'district_agency_register' => ['require','in:1,2'],
        'district_agency_money' => ['checkDistrictAgencyMoney'],
        'operator_register' => ['require','in:1,2'],
        'operator_money' => ['checkOperatorMoney'],
    ];

    protected $message = [
        'agency_first_money.require' => '请填写初级代理开通费用',
        'agency_first_money.egt' => '初级代理开通费用不能小于0.01',
        'agency_high_money.require' => '请填写高级代理开通费用',
        'agency_high_money.egt' => '高级代理开通费用不能小于0.01',
        'big_agency_register.require' => '请选择大区代理注册开关',
        'big_agency_register.in' => '请选择大区代理注册开关',
        'district_agency_register.require' => '请选择区域代理注册开关',
        'district_agency_register.in' => '请选择区域代理注册开关',
        'operator_register.require' => '请选择运营商注册开关',
        'operator_register.in' => '请选择运营商注册开关',

    ];



    // 检查高级代理价格
    protected function checkAgencyHighMoney($value,$rule,$data=[])
    {
        if($value <= $data['agency_first_money']){
            return '高级代理费用必须大于初级代理';
        }
        return true;
    }



    // 检查大区代理开通金额
    protected function checkBigAgencyMoney($value,$rule,$data=[])
    {
        if($data['big_agency_register'] == 1){
            if(abs(floatval($value)) < 0.01){
                return '大区代理开通费用不能小于0.01';
            }
        }
        return true;
    }



    // 检查区域代理开通金额
    protected function checkDistrictAgencyMoney($value,$rule,$data=[])
    {
        if($data['district_agency_register'] == 1){
            if(abs(floatval($value)) < 0.01){
                return '区域代理开通费用不能小于0.01';
            }
        }
        return true;
    }

    // 检查运营商开通金额
    protected function checkOperatorMoney($value,$rule,$data=[])
    {
        if($data['operator_register'] == 1){
            if(abs(floatval($value)) < 0.01){
                return '运营商开通费用不能小于0.01';
            }
        }
        return true;
    }
}
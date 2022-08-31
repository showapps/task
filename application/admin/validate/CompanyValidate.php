<?php
/**
 * CompanyValidate.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/13
 */

namespace app\admin\validate;


use think\Validate;

class CompanyValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'ids' => ['require', 'array'],
        'status' => ['require','in:3,100'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'ids.require' => '请选择数据',
        'ids.array' => '请选择数据',
        'status.require' => '状态必须选择',
        'status.in' => '无效的状态值',
    ];


    protected $scene = [
        'detail' => ['id'],
        'auditView' => ['id'],
        'audit' => ['id','status'],
        'query_gongshang' => ['id'],
        'update_gongshang' => ['id'],
    ];

}
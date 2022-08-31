<?php
/**
 * AdminValidate.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/15
 */

namespace app\admin\validate;


use think\Validate;

class AdminValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'ids' => ['require', 'array'],
        'user_id' => ['require', 'number'],
        'role_id' => ['require', 'number'],
        'title' => ['require'],
        'status' => ['require','in:1,2'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'ids.require' => '请选择数据',
        'ids.array' => '请选择数据',
        'user_id.require' => '请选择用户',
        'user_id.number' => '选择用户无效',
        'role_id.require' => '请选择角色',
        'role_id.number' => '选择角色无效',
        'status.require' => '状态必须选择',
        'status.in' => '无效的状态值',
    ];


    protected $scene = [
        'detail' => ['id'],
        'create' => ['user_id','role_id',],
        'updateView' => ['id'],
        'update' => ['id'],
        'delete' => ['id'],
    ];

}
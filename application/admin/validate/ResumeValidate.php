<?php
/**
 * ResumeValidate.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/16
 */

namespace app\admin\validate;


use think\Validate;

class ResumeValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
    ];


    protected $scene = [
        'detail' => ['id'],
    ];

}
<?php

namespace app\admin\validate;


use think\Validate;

class AdValidate extends Validate
{

    protected $rule = [
        'id' => ['require', 'number'],
        'title' => ['require','length:1,80'],
        'position' => ['require','alphaDash','length:1,32'],
        'type' => ['require','in:1,2,3'],
        'width' => ['require','number'],
        'height' => ['require','number'],
        'content' => ['require','array'],
        'status' => ['require','in:1,2'],
    ];

    protected $message = [
        'id.require' => '请选择数据',
        'id.number' => '选择数据无效',
        'title.require' => '标题必填',
        'title.length' => '标题长度最大80位',
        'position.require' => '位置标识必填',
        'position.alphaDash' => '位置标识只支持 字母、数字、下划线 组合',
        'position.length' => '位置标识最大32位',
        'type.require' => '类型必须选择',
        'type.in' => '无效的类型值',
        'width.require' => '宽度必填',
        'width.number' => '宽度必须是数字',
        'height.require' => '高度必填',
        'height.number' => '高度必须是数字',
        'content.require' => '素材必须上传',
        'content.array' => '素材类型无效',
        'status.require' => '状态必须选择',
        'status.in' => '状态类型无效',
    ];


    protected $scene = [
        'detail' => ['id'],
        'create' => ['title', 'position', 'type', 'width', 'height', 'content', 'status'],
        'updateView' => ['id'],
        'update' => ['id', 'content', 'status'],
        'delete' => ['id'],
    ];



    // 检查时间
    protected function checkDates($value,$rule,$data=[])
    {

        $start_time = strtotime($data['start_dt']);
        $end_time = strtotime($value);

        if($end_time < $start_time){
            return '结束时间不能小于开始时间';
        }

        return true;
    }

}
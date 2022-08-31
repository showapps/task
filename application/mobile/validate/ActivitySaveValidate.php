<?php


namespace app\mobile\validate;


use think\Validate;

class ActivitySaveValidate extends Validate
{

    protected $rule =   [
        'id'  => ['require','number'],
        'category_id'  => ['require','number','checkCategory'],
        'project_title'  => ['require','length:4,20'],
        'title'  => ['require','length:1,10'],
        'link'  => ['length:1,120'],
        'steps'  => ['require','array','checkStep'],
        'text_require'  => ['length:1,30'],
        'price'  => ['require','float','checkPrice'],
        'total'  => ['require','number','checkTotal'],
        'end_dt'  => ['dateFormat:Y-m-d'],
        'limited_submit'  => ['require','in:2,3,4,5,6,24,48,72'],
        'audit_cycle'  => ['require','in:24,48,72'],
    ];

    protected $message  =   [
        'id.require' => '请选择活动',
        'id.number' => '活动值无效',
        'category_id.require' => '请选择活动分类',
        'category_id.number' => '选择的活动分类无效',
        'project_title.require' => '项目名称必填',
        'project_title.length' => '项目名称长度5~20位之间',
        'title.require' => '任务标题必填',
        'title.length' => '任务标题长度1~10位之间',
        'link.length' => '任务链接长度1~120位之间',
        'steps.require' => '请设置任务步骤',
        'steps.array' => '任务步骤无效',
        'text_require.length' => '提交数据长度1~30位之间',
        'price.require' => '投放单价必填',
        'price.float' => '投放单价必须是数字',
        'total.require' => '投放数量必填',
        'total.number' => '投放数量无效',
        'end_dt.dateFormat' => '结束日期格式错误',
        'limited_submit.require' => '限时提交必填',
        'limited_submit.in' => '限时提交无效',
        'audit_cycle.require' => '审核周期必填',
        'audit_cycle.in' => '审核周期无效',
    ];


    protected $scene = [
        'create'  =>  ['category_id', 'project_title', 'title', 'link', 'steps','text_require', 'price', 'total', 'end_dt', 'limited_submit', 'audit_cycle'],
        'update'  =>  ['id', 'project_title', 'title', 'link', 'steps','text_require', 'end_dt', 'limited_submit', 'audit_cycle'],
    ];



    // 检查分类
    protected function checkCategory($value,$rule,$data=[])
    {

        if(isset($data['category']) && $data['category']){
            return true;
        }

        return '分类不存在';
    }



    // 检查步骤
    protected function checkStep($value,$rule,$data=[])
    {

        if(count($value) < 1){
            return '请设置任务步骤';
        }

        $total = 0;
        foreach ($value as $row){

            if(isset($row['type']) && isset($row['describe']) && isset($row['image'])){
                $total ++;
                if($row['type'] == 2){
                    return true;
                }
            }

        }

        if($total > 15){
            return '最多只能添加15个步骤';
        }

        return '请最少添加一个收集截图类型的步骤';
    }



    // 检查投放单价
    protected function checkPrice($value,$rule,$data=[])
    {

        $min_price = fen_to_float($data['category']['min_price']);
        if($value >= $min_price){
            return true;
        }

        return '投放单价不能小于'.$min_price.'元';
    }



    // 检查最小单价
    protected function checkTotal($value,$rule,$data=[])
    {

        $min_number = $data['category']['min_number'];
        if($value >= $min_number){
            return true;
        }

        return '投放数量不能小于'.$min_number.'个';
    }

}
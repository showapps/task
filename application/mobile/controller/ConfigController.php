<?php

namespace app\mobile\controller;


use app\common\model\ConfigModel;
use Tools\DbConfig;
use Tools\Responses;

class ConfigController
{


    public function install()
    {

        $data = [
            'name'=>'vip',
            'title'=>'会员配置',
            'parent_id'=>0,
            'category'=>1,
            'type'=>0,
            'content'=>'',
            'tips'=>'',
            'status'=>1,
            'is_env'=>2,
            'is_mobile'=>1,
            'is_admin'=>1,
            'childs'=>[]
        ];

        $data['childs'][] = [
            'name'=>'user_vip_threshold',
            'title'=>'喵达人',
            'type'=>2,
            'content'=>1,
            'tips'=>'',
            'status'=>1,
            'is_env'=>1,
            'is_mobile'=>2,
            'is_admin'=>1,
        ];


        $configCount = ConfigModel::where('name','=',$data['name'])->count();
        if(!$configCount){
            $root_data = $data;
            unset($root_data['childs']);
            $config = ConfigModel::create($root_data);
            if ($config && $data['childs']){
                foreach ($data['childs'] as $row){

                    $row['name'] = $config['name'].'.'.$row['name'];
                    $row['parent_id'] = $config['id'];
                    $row['category'] = $config['category'];

                    ConfigModel::create($row);

                }
            }
        }

        $dbConfig = new DbConfig();
        $dbConfig->reset();

        return Responses::data(200,'success');

    }

}
<?php

namespace app\mobile\controller;


use app\common\model\AdminNodeModel;
use app\common\model\ConfigModel;
use Tools\DbConfig;
use Tools\Responses;

class MenuController
{


    public function install()
    {

        $data = [
            'name'=>'rankinglist',
            'title'=>'排行榜管理',
            'parent_id'=>0,
            'type'=>1,
            'describe'=>'',
            'sort'=>1013,
            'status'=>1,
            'is_system'=>1,
            'childs'=>[]
        ];

        $data['childs'][] = [
            'name'=>'read',
            'title'=>'阅读',
        ];


        $menuCount = AdminNodeModel::where('name','=',$data['name'])->count();
        if(!$menuCount){
            $root_data = $data;
            unset($root_data['childs']);
            $menu = AdminNodeModel::create($root_data);
            if ($menu && $data['childs']){
                $child_sort = $menu['sort'] . '00';
                foreach ($data['childs'] as $row){
                    $child_sort ++;
                    $row['parent_id'] = $menu['id'];
                    $row['type'] = 2;
                    $row['sort'] = $child_sort;
                    $row['describe'] = '';
                    $row['status'] = $menu['status'];
                    $row['is_system'] = $menu['is_system'];
                    AdminNodeModel::create($row);
                }
            }
        }

        return Responses::data(200,'success');

    }

}
<?php
// +----------------------------------------------------------------------
// | 科创网贷超市系统 Pro
// +----------------------------------------------------------------------
// | Copyright (c) 2017-2019 https://www.kechuang.link All rights reserved.
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
// | Author: 深圳科创软件有限公司 <service@kechuang.link>
// +----------------------------------------------------------------------


namespace Tools;


use app\common\model\AdminAccessModel;
use app\common\model\AdminNodeModel;
use think\facade\Cache;


class AdminPermission
{

    protected $role_id = null;
    protected $is_root = false;
    protected $module_name = null;
    protected $action_name = null;
    protected $accessList = null;
    protected $accessTree = null;


    public function setRoleId($role_id)
    {
        $this->role_id = $role_id;
        $this->is_root = $this->role_id == 1 ? true : false;
    }

    public function setModuleName($module_name)
    {
        $this->module_name = strtoupper($module_name);
        return $this;
    }


    public function setActionName($action_name)
    {
        $this->action_name = strtoupper($action_name);
        return $this;
    }


    public function getAccessTree()
    {
        $cacheKey = 'AdminPermission_getAccessTree_'.$this->role_id;

        if(is_null($this->accessTree)){

            $cache = Cache::get($cacheKey,[]);
            if($cache && is_array($cache) && adminRoleUpdataVersion($this->role_id) < $cache['cache_time']){
                $this->accessTree = $cache['tree'];
            }else{

                $tree = [];
                $access_ids = AdminAccessModel::where('role_id','=',$this->role_id)->column('node_id');
                if($access_ids){
                    $nodeList = AdminNodeModel::where('parent_id','=',0)
                        ->where('type','=',1)
                        ->where('id','in',$access_ids)
                        ->field('id,name,title')
                        ->select();

                    if($nodeList){
                        foreach ($nodeList as $node){

                            $treeRow = [
                              'id'=>$node['id'],
                              'name'=>$node['name'],
                              'title'=>$node['title'],
                              'child_nodes'=>[],
                            ];

                            $child_nodes = AdminNodeModel::where('parent_id','=',$node->id)
                                ->where('type','=',2)
                                ->where('id','in',$access_ids)
                                ->where('status','=',1)
                                ->field('id,name,title')
                                ->select();

                            if($child_nodes){
                                foreach ($child_nodes as $tow){
                                    $treeRow['child_nodes'][strtoupper($tow['name'])] = $tow;
                                }
                            }

                            $tree[strtoupper($node['name'])] = $treeRow;

                        }
                    }

                }

                $this->accessTree = $tree;
                if($this->accessTree && is_array($this->accessTree)){

                    $cacheData = [
                        'tree'=>$tree,
                        'cache_time'=>time(),
                    ];

                    Cache::set($cacheKey,$cacheData,86400);
                }
            }

        }

        return $this->accessTree;

    }



    public function checkActionAccess()
    {
        if($this->is_root == true){
            return true;
        }

        //获取权限树结构
        $this->getAccessTree();

        return isset($this->accessTree[$this->module_name]['child_nodes'][$this->action_name]) ? true : false;
    }


    public function checkModuleAccess()
    {
        if($this->is_root == true){
            return true;
        }

        //获取权限树结构
        $this->getAccessTree();
        return isset($this->accessTree[$this->module_name]) ? true : false;
    }


}
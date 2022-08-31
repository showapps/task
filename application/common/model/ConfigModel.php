<?php
/**
 * ConfigModel.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/9/25
 */

namespace app\common\model;


use think\facade\Cache;
use think\Model;
use Tools\DbConfig;

class ConfigModel extends Model
{

    protected $table = 'configs';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';


    public static function getMobile(){

        //初始化所有配置
        $configs = Cache::get('db_config_plus_mobile',[]);
        if(!$configs){

            $list = ConfigModel::where('is_mobile','=',1)
                ->order('id ASC')
                ->select();

            $configs = [];
            $name_indexs = [];
            if($list){
                foreach ($list as $item){

                    //处理一级
                    if($item['type'] == '0'){
                        $name = strtolower($item['name']);
                        $name_indexs[$item['id']] = $name;
                        $configs[$name] = [];
                    }else{
                        $name = strtolower($item['name']);
                        $name = trim(strstr($name,'.'),'.');

                        if(isset($name_indexs[$item['parent_id']])){
                            $parent_name = $name_indexs[$item['parent_id']];
                            //获取 env 配置
                            $content = $item['is_env'] == 1 ? env(str_replace('.','_',$item['name']),'') : $item['content'];
                            $configs[$parent_name][$name] = DbConfig::dataFormatting($item['type'],$content);
                        }

                    }

                }
            }


            Cache::set('db_config_plus_mobile',$configs,86400 * 3);

        }
        return $configs;
    }

}
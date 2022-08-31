<?php
/**
 * DbConfig.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/1
 */

namespace Tools;


use app\common\model\ConfigModel;
use think\facade\Cache;

class DbConfig
{

    private static $configs = null;
    private $cacheKey = 'db_config_plus';
    private $dataTypeDefault = [
        1=>'',
        2=>0,
        3=>0.00,
        4=>2,
        5=>'[]',
    ];

    /**
     * 构造函数
     * */
    public function __construct()
    {
        if(is_null(self::$configs)){
            $this->init();
        }

    }




    /**
     * 初始化
     * */
    protected function init(){

        //初始化所有配置
        $configs = Cache::get($this->cacheKey,[]);
        if(!$configs){

            $list = ConfigModel::order('id ASC')
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

            Cache::set($this->cacheKey,$configs,86400);
        }

        self::$configs = $configs;
        return true;

    }




    /**
     * 重置
     *
     * @return bool
     * */
    public function reset(){

        Cache::set($this->cacheKey,null,60);
        self::$configs = null;
        //return $this->init();

    }



    /**
     * 数据格式化
     *
     * @param int $type
     * @param string $val
     * @return mixed
     * */
    static public function dataFormatting($type,$val){

        switch ($type){
            case 1://字符串
                return $val;
                break;
            case 2://整形
                return (int)$val;
                break;
            case 3://浮点型
                return (float)$val;
                break;
            case 4://布尔型
                return (bool)($val == 1 || $val === true || $val == 'true');
                break;
            case 5://JSON
                return $val;
                break;
        }

    }




    /**
     * 读取配置（单个）
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     * */
    public function get(string $name,$default = null){

        $name = strtolower($name);

        $data = [];
        if(stripos($name,'.') === false){
            if(!isset(self::$configs[$name])){
                return $default;
            }
            $data = self::$configs[$name];
        }else{
            $paths = explode('.',$name);
            if((!isset(self::$configs[$paths[0]])) || (!isset(self::$configs[$paths[0]][$paths[1]]))){
                return $default;
            }
            $data = self::$configs[$paths[0]][$paths[1]];
        }

        return $data;

    }




    /**
     * 读取配置（多个）
     *
     * @param array $params
     * @return array
     * */
    public function getList($params){

        $configs = [];

        foreach ($params as $param){
            $name = strtolower($param[0]);
            $default = isset($param[1]) ? $param[1] : null;
            $configs[$name] = $this->get($name,$default);
        }

        return $configs;

    }

}
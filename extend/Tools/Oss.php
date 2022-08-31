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


use AliOSS\OssClient;

class Oss
{

    private static $instance = null;
    private static $error = null;
    private static $ossObj = null;
    private static $config = [];
    private static $params = [
        'file_type'=>'image',
        'save_root'=>'',
        'save_local_root'=>'',
        'save_path'=>''
    ];

    private function __construct()
    {

    }


    /**
     * 获取实例
     * @param array $config
     * @return self
     */
    public static function instance(array $config)
    {
        if (!(self::$instance instanceof self)){
            self::$instance = new self();
            self::$config = $config;
        }
        return self::$instance;
    }



    /**
     * OssClient
     *
     * @return OssClient
     */
    private static function ossClient()
    {
        if (!(self::$ossObj instanceof OssClient)){
            self::$ossObj = new OssClient(self::$config['access_id'],self::$config['access_secret'],self::$config['endpoint']);
        }
        return self::$ossObj;
    }




    /**
     * 设置
     *
     * @param  string  $key
     * @param  string  $val
     * @return self
     */
    public function set($key,$val)
    {
        self::$params[$key] = $val;
        return self::$instance;
    }



    /**
     * 获取错误消息
     *
     * @return string
     */
    public function getErrorMessage()
    {

        //dd([self::$error]);

        if(self::$error){
            return self::$error;
        }

        return '';
    }



    /**
     * 对象推送
     *
     * @param  string  $path
     * @param  string  $content
     * @return string
     */
    public function push($path,$content)
    {

        //上传到 oss
        $ossReturn = self::ossClient()->putObject(self::$config['bucket'], $path,$content);

        //判断 状态码
        if($ossReturn['info']['http_code'] != 200){
            return false;
        }

        return $path;

    }




    /**
     * 文件上传
     *
     * @return array
     */
    public function uploads()
    {

        self::$error = null;

        //处理目录
        self::$params['save_root'] = trim(self::$params['save_root'],'/');
        self::$params['save_root'] = !empty(self::$params['save_root']) ? self::$params['save_root'] . '/' : self::$params['save_root'];

        self::$params['save_path'] = trim(self::$params['save_path'],'/');
        self::$params['save_path'] = !empty(self::$params['save_path']) ? self::$params['save_path'] : self::$params['save_path'];

        //$_FILES error 对应错误提示
        $filesErrors = array(
            1=>'文件超过最大限制大小',//php.ini 中 upload_max_filesize
            2=>'文件超过最大限制大小',//HTML 表单中 MAX_FILE_SIZE
            3=>'文件只有部分被上传',
            4=>'没有文件被上传',
            5=>'上传文件大小为 0'
        );

        $maxSize = (int)self::$config[self::$params['file_type']]['max_size'];
        $allowExts = self::$config[self::$params['file_type']]['allow_exts'];
        $allowExts = $allowExts && is_array($allowExts) ? $allowExts : [];
        $path = self::$params['save_root'] . self::$params['save_path'];
        $url_header = self::$config['domain_header'];
        $return = [];

        foreach ($_FILES as $file){

            //获取path信息
            $pathinfo = pathinfo($file['name']);
            //文件扩展名 小写
            $fileExt = isset($pathinfo['extension']) && $pathinfo['extension'] ? strtolower($pathinfo['extension']) : 'jpg' ;

            //上传失败
            if($file['error'] != 0){
                self::$error = isset($filesErrors[$file['error']]) ? $filesErrors[$file['error']] : '未知错误';
                return false;
            }

            if($maxSize){//限制上传大小
                if($file['size'] > $maxSize){//超过允许大小
                    self::$error = '文件超过最大限制大小';
                    return false;
                }
            }

            $fileExt = strtolower($fileExt);
            if($allowExts){//有设置限制允许后缀
                //判断上传文件后缀
                if(!in_array($fileExt,$allowExts)){
                    self::$error = '只允许上传 '.implode('|',$allowExts).' 后缀文件';
                    return false;
                }
            }

            /*组合返回单个文件上传信息*/
            $info = [];
            //文件名
            $info['name'] = time().mt_rand(000,999).mt_rand(00,99).'.'.$fileExt;
            $info['path'] = $path.'/'.$info['name'];
            $info['save_path'] = $path;
            //文件后缀
            $info['extension'] = $fileExt;
            //文件大小
            $info['size'] = $file['size'];
            //url
            $info['url'] = $url_header.'/'.$info['path'];

            //读文件字节流
            $content = file_get_contents($file['tmp_name']);
            /* 移动本地 */
            if(self::$params['save_local_root']){

                if(!is_dir(env('root_path/public').self::$params['save_local_root'])){
                    self::$error = '本地上传目录不存在';
                    return false;
                }

                $local_path_root = rtrim(env('root_path/public').self::$params['save_local_root'],'/');

                $local_path = $local_path_root.'/'.self::$params['save_path'];
                if(!is_dir($local_path)){
                    mkdirs($local_path_root,self::$params['save_path'],0755);
                }

                file_put_contents($local_path.'/'.$info['name'],$content);

            }

            /* 上传 OSS */
            //执行上传-上传失败
            if(!$this->push($info['path'],$content)){
                self::$error = '上传到储存服务器失败';
                return false;
            }

            $return[] = $info;

        }

        self::$error = '';
        return $return;
    }
}
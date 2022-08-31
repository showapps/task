<?php
/**
 * UploadController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2019-11-19
 */

namespace app\service\controller;

use think\Exception;
use think\facade\Request;
use Tools\Oss;
use Tools\Responses;

class UploadController
{

    private $ossConfig = [];


    public function __construct()
    {

        $this->ossConfig = dbConfig('oss',[]);
        if((!$this->ossConfig) || (!$this->ossConfig['access_id'])){
            throw new Exception('未开启上传功能',50001);
        }

        $this->ossConfig['image'] = [
            'max_size'=>$this->ossConfig['image_max_size'],
            'allow_exts'=>$this->ossConfig['image_allow_exts'],
        ];

        $this->ossConfig['video'] = [
            'max_size'=>$this->ossConfig['video_max_size'],
            'allow_exts'=>$this->ossConfig['video_max_size'],
        ];

        $this->ossConfig['voice'] = [
            'max_size'=>$this->ossConfig['voice_max_size'],
            'allow_exts'=>$this->ossConfig['voice_max_size'],
        ];

    }





    /**
     * 数据首页
     * */
    public function kindeditorImage()
    {

        //有图片上传
        if(!empty($_FILES)) {

            try{

                $upload = Oss::instance($this->ossConfig);
                $result = $upload->set('file_type','image')
                    ->set('save_root','task/uploads/images/')
                    ->set('save_path',date('Y/m/d').'/')
                    ->uploads();

                if(!$result){
                    throw new Exception($upload->getErrorMessage(),40004);
                }

                return json(['error' => 0, 'url' => $result[0]['url']]);

            }catch (\Exception $e){
                return json(['error' => 1, 'message' => $e->getMessage()]);
            }

        }

        return json(['error' => 1, 'message' => '上传数据不存在']);

    }



    /**
     * 图片上传 base64
     * */
    public function image_base64()
    {

        $image_content = Request::post('content','');
        //有图片上传
        if(!empty($image_content)) {

            try{

                $oss = Oss::instance($this->ossConfig);

                //匹配出图片的格式
                if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $image_content, $result)){
                    //后缀
                    $fileExt = $result[2];
                    $content = base64_decode(str_replace($result[1], '', $image_content));
                }else{
                    throw new Exception('上传失败：图片内容不存在',40004);
                }

                $name = time().rand(111,999).rand(111,999).'.'.$fileExt;

                $savePath = 'task/uploads/images/'.date('Y/m/d').'/'.$name;

                $result = $oss->push($savePath,$content);

                if(!$result){
                    throw new Exception('上传失败：图片上传到服务器失败',40004);
                }

                $info = [];
                //文件名
                $info['name'] = $name;
                $info['path'] = $savePath;
                $info['save_path'] = $savePath;
                //文件后缀
                $info['extension'] = $fileExt;
                //文件大小
                $info['size'] = 0;
                //url
                $info['url'] = $this->ossConfig['domain_header'].'/'.$info['path'];

                return Responses::data(200,'success',$info);

            }catch (\Exception $e){
                throw new Exception($e->getMessage(),40004);
            }

        }

        return Responses::data(40004,'上传数据不存在');

    }



    /**
     * 图片上传
     * */
    public function images()
    {

        //有图片上传
        if(!empty($_FILES)) {

            try{

                $upload = Oss::instance($this->ossConfig);
                $result = $upload->set('file_type','image')
                    ->set('save_root','task/uploads/images/')
                    ->set('save_path',date('Y/m/d').'/')
                    ->uploads();

                if(!$result){
                    throw new Exception($upload->getErrorMessage(),40004);
                }

                return Responses::data(200,'success',$result);

            }catch (\Exception $e){
                throw new Exception($e->getMessage(),40004);
            }

        }

        return Responses::data(40004,'上传数据不存在');

    }



    /**
     * excels上传
     * */
    public function excels()
    {

        //有图片上传
        if(!empty($_FILES)) {

            try{

                $in_local = Request::get('in_local','','trim');

                $upload = Oss::instance($this->ossConfig);
                if($in_local){
                    $upload = $upload->set('save_local_root','uploads/excels/');
                }

                $result = $upload->set('file_type','excel')
                    ->set('save_root','task/uploads/excels/')
                    ->set('save_path',date('Y/m/d').'/')
                    ->uploads();

                if(!$result){
                    throw new Exception($upload->getErrorMessage(),40004);
                }

                return Responses::data(200,'success',$result);

            }catch (\Exception $e){
                throw new Exception($e->getMessage(),40004);
            }

        }

        return Responses::data(40004,'上传数据不存在');

    }


    /**
     * 视频上传
     * */
    public function videos()
    {
        //有文件上传
        if(!empty($_FILES)) {

            try{

                $upload = Oss::instance($this->ossConfig);
                $result = $upload->set('file_type','video')
                    ->set('save_root','task/uploads/videos/')
                    ->set('save_path',date('Y/m/d').'/')
                    ->uploads();

                if(!$result){
                    throw new Exception($upload->getErrorMessage(),40004);
                }

                return Responses::data(200,'success',$result);

            }catch (\Exception $e){
                throw new Exception($e->getMessage(),40004);
            }

        }

        return Responses::data(40004,'上传数据不存在');

    }


    /**
     * 音频上传
     * */
    public function voices()
    {
        //有文件上传
        if(!empty($_FILES)) {

            try{

                $upload = Oss::instance($this->ossConfig);
                $result = $upload->set('file_type','voice')
                    ->set('save_root','task/uploads/voices/')
                    ->set('save_path',date('Y/m/d').'/')
                    ->uploads();

                if(!$result){
                    throw new Exception($upload->getErrorMessage(),40004);
                }

                return Responses::data(200,'success',$result);

            }catch (\Exception $e){
                throw new Exception($e->getMessage(),40004);
            }
        }

        return Responses::data(40004,'上传数据不存在');

    }


}
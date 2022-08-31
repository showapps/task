<?php
/**
 * ArticleController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/17
 */

namespace app\admin\controller;


use app\admin\traits\AuthTrait;
use app\common\exception\DbException;
use app\common\exception\RequestException;
use app\common\model\AdModel;
use app\admin\validate\AdValidate;
use think\facade\Hook;
use think\facade\Request;
use think\facade\View;
use Tools\Responses;

class AdController
{

    use AuthTrait;

    protected $directory = 'ad';

    public function __construct()
    {
        $this->initAuthInfo();
    }





    public function index()
    {
        return view($this->directory . '/index');
    }




    /**
     * 数据列表
     *
     * @return Responses
     * */
    public function lists()
    {

        $AdModel = new AdModel();

        //处理过滤
        $filters = Request::post('filters',[]);

        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $AdModel = $AdModel->where('id','in',$value);
                        }
                        break;
                    case 'search_text':
                        $value = htmlspecialchars(strip_tags(trim($value)));
                        if($value){
                            $AdModel = $AdModel->where('(title like \''.$value.'%\' OR position like \''.$value.'%\')');
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $AdModel
            ->order('id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = $lists['data'];

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


    /**
     * 数据详情
     *
     * @return Responses
     * */
    public function detail()
    {

        //表单验证
        $validate = new AdValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::param());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::param('id');
        $info = AdModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '广告不存在',40401);
        }

        $data = (string)View::fetch($this->directory . '/detail',compact('info'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 添加模板
     *
     * @return Responses
     * */
    public function createView()
    {

        $data = (string)View::fetch($this->directory . '/add');
        return Responses::data(200, 'success',$data);

    }


    /**
     * 添加广告
     *
     * @return Responses
     * */
    public function create()
    {

        //表单验证
        $validate = new AdValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $data = [];
        $data['title'] = Request::post('title','','trim,strip_tags,htmlspecialchars');
        $data['position'] = Request::post('position','','trim,strip_tags,htmlspecialchars');
        $data['type'] = Request::post('type',0,'intval');
        $data['sort'] = Request::post('sort',0,'intval,abs');
        $data['width'] = Request::post('width',0,'intval');
        $data['height'] = Request::post('height',0,'intval');
        $data['status'] = Request::post('status',2,'intval') == 1 ? 1 : 2;

        //处理内容
        $contents  = Request::post('content',[]);
        $data['content'] = [];
        $type = $data['type'];
        if($contents && is_array($contents)){
            foreach ($contents as $row){
                if(isset($row['resource']) && $row['resource']){
                    $content = [];
                    $content['resource'] = (string)$row['resource'];
                    if(isset($row['links']) && $row['links'] && is_array($row['links'])){
                        $links = $row['links'];
                        if(isset($links['method']) && $links['method'] && isset($links['url']) && $links['url']){
                            $content['links'] = [
                                'method'=>$links['method'],
                                'url'=>$links['url'],
                            ];
                        }
                    }
                    //单个
                    if(in_array($type,[1,3])){
                        $data['content'] = $content;
                    }else{
                        $data['content'][] = $content;
                    }

                }
            }
        }

        $positionCount = AdModel::where('position','=',$data['position'])->count();
        if($positionCount >= 1){
            throw new DbException('添加失败:位置标识已被占用',40003);
        }

        try {

            $result = AdModel::create($data);
            if(!$result){
                throw new DbException('添加失败',50001);
            }

            Hook::listen('ad_update');
            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


    /**
     * 编辑模板
     *
     * @return Responses
     * */
    public function updateView()
    {

        //表单验证
        $validate = new AdValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = AdModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '广告不存在',40401 );
        }

        $data = (string)View::fetch($this->directory . '/edit',compact('info'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 编辑广告
     *
     * @return Responses
     * */
    public function update()
    {

        //表单验证
        $validate = new AdValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = AdModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '广告不存在',40401 );
        }

        $data = [];
        $data['sort'] = Request::post('sort',0,'intval,abs');
        $data['status'] = Request::post('status',2,'intval') == 1 ? 1 : 2;
        $data['updated_at'] = date('Y-m-d H:i:s');

        //处理内容
        $contents  = Request::post('content',[]);
        $data['content'] = [];
        $type = $info['type'];
        if($contents && is_array($contents)){
            foreach ($contents as $row){
                if(isset($row['resource']) && $row['resource']){
                    $content = [];
                    $content['resource'] = (string)$row['resource'];
                    if(isset($row['links']) && $row['links'] && is_array($row['links'])){
                        $links = $row['links'];
                        if(isset($links['method']) && $links['method'] && isset($links['url']) && $links['url']){
                            $content['links'] = [
                                'method'=>$links['method'],
                                'url'=>$links['url'],
                            ];
                        }
                    }
                    //单个
                    if(in_array($type,[1,3])){
                        $data['content'] = $content;
                    }else{
                        $data['content'][] = $content;
                    }

                }
            }
        }

        try {

            $result = AdModel::where('id','=',$id)->update($data);
            if(!$result){
                throw new DbException('更新失败',50001);
            }

            Hook::listen('ad_update');
            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


    /**
     * 广告删除
     *
     * @return Responses
     * */
    public function delete()
    {

        //表单验证
        $validate = new AdValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        try {

            AdModel::where('id','=',$id)->delete();

            Hook::listen('ad_update');
            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


}
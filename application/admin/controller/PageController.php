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
use app\common\model\PageModel;
use app\admin\validate\PageValidate;
use think\facade\Request;
use think\facade\View;
use Tools\Responses;

class PageController
{

    use AuthTrait;

    protected $directory = 'page';

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

        $PageModel = new PageModel();

        //处理过滤
        $filters = Request::post('filters',[]);

        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $PageModel = $PageModel->where('id','in',$value);
                        }
                        break;
                    case 'search_text':
                        $value = htmlspecialchars(strip_tags(trim($value)));
                        if($value){
                            $PageModel = $PageModel->where('(title like \''.$value.'%\' OR name like \''.$value.'%\')');
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $PageModel
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
        $validate = new PageValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::param());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::param('id');
        $info = PageModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '文章不存在',40401);
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
     * 添加文章
     *
     * @return Responses
     * */
    public function create()
    {

        //表单验证
        $validate = new PageValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $data = [];
        $data['title'] = Request::post('title','','trim,strip_tags,htmlspecialchars');
        $data['name'] = Request::post('name','','trim,strip_tags,htmlspecialchars');
        $data['status'] = Request::post('status',2,'intval') == 1 ? 1 : 2;
        $data['sort'] = Request::post('sort',0,'intval');
        $data['content'] = Request::post('content','','trim');

        $nameCount = PageModel::where('name','=',$data['name'])->count();
        if($nameCount >= 1){
            throw new DbException('添加失败:单页名称已被占用',40003);
        }

        try {

            $result = PageModel::create($data);
            if(!$result){
                throw new DbException('添加失败',50001);
            }

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
        $validate = new PageValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = PageModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '单页不存在',40401 );
        }

        $data = (string)View::fetch($this->directory . '/edit',compact('info'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 编辑文章
     *
     * @return Responses
     * */
    public function update()
    {

        //表单验证
        $validate = new PageValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = PageModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '文章不存在',40401 );
        }

        $data = [];
        $data['title'] = Request::post('title','','trim,strip_tags,htmlspecialchars');
        //$data['name'] = Request::post('name','','trim,strip_tags,htmlspecialchars');
        $data['status'] = Request::post('status',2,'intval') == 1 ? 1 : 2;
        $data['sort'] = Request::post('sort',0,'intval');
        $data['content'] = Request::post('content','','trim');
        $data['updated_at'] = date('Y-m-d H:i:s');

        /*
        $nameCount = PageModel::where('name','=',$data['name'])
            ->where('id','<>',$id)
            ->count();
        if($nameCount >= 1){
            throw new DbException('更新失败:单页名称已被占用',40003);
        }
        */

        try {

            $result = PageModel::where('id','=',$id)->update($data);
            if(!$result){
                throw new DbException('更新失败',50001);
            }

            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


    /**
     * 文章删除
     *
     * @return Responses
     * */
    public function delete()
    {

        //表单验证
        $validate = new PageValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        try {

            PageModel::where('id','=',$id)->delete();
            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


}
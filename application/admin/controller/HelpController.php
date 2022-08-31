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
use app\common\model\HelpCategoryModel;
use app\common\model\HelpModel;
use app\admin\validate\HelpValidate;
use think\facade\Request;
use think\facade\View;
use Tools\Responses;

class HelpController
{

    use AuthTrait;

    protected $directory = 'help';

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

        $HelpModel = new HelpModel();

        //处理过滤
        $filters = Request::post('filters',[]);

        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $HelpModel = $HelpModel->where('id','in',$value);
                        }
                        break;
                    case 'search_text':
                        $value = htmlspecialchars(strip_tags(trim($value)));
                        if($value){
                            $HelpModel = $HelpModel->where('title','like',$value.'%');
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $HelpModel
            ->with([
                'category'=>function($query){
                    $query->field('id,title');
                }
            ])
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
        $validate = new HelpValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::param());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::param('id');
        $info = HelpModel::where('id','=',$id)
            ->with([
                'category'=>function($query){
                    $query->field('id,title');
                }
            ])
            ->find();
        if(!$info){
            throw new RequestException( '帮助不存在',40401);
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
        $categorys = HelpCategoryModel::select();
        $data = (string)View::fetch($this->directory . '/add',compact('categorys'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 添加帮助
     *
     * @return Responses
     * */
    public function create()
    {

        //表单验证
        $validate = new HelpValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $data = [];
        $data['title'] = Request::post('title','','trim,strip_tags,htmlspecialchars');
        $data['category_id'] = Request::post('category_id',0,'intval');
        $data['status'] = Request::post('status',2,'intval') == 1 ? 1 : 2;
        $data['sort'] = Request::post('sort',0,'intval,abs');
        $data['content'] = Request::post('content','','trim');

        $category_count = HelpCategoryModel::where('id','=',$data['category_id'])->count();
        if($category_count < 1){
            throw new DbException('添加失败:分类不存在',50001);
        }

        try {

            $result = HelpModel::create($data);
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
        $validate = new HelpValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = HelpModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '帮助不存在',40401 );
        }

        $categorys = HelpCategoryModel::select();
        $data = (string)View::fetch($this->directory . '/edit',compact('info','categorys'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 编辑帮助
     *
     * @return Responses
     * */
    public function update()
    {

        //表单验证
        $validate = new HelpValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = HelpModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '帮助不存在',40401 );
        }

        $data = [];
        $data['title'] = Request::post('title','','trim,strip_tags,htmlspecialchars');
        $data['category_id'] = Request::post('category_id',0,'intval');
        $data['status'] = Request::post('status',2,'intval') == 1 ? 1 : 2;
        $data['sort'] = Request::post('sort',0,'intval,abs');
        $data['content'] = Request::post('content','','trim');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $category_count = HelpCategoryModel::where('id','=',$data['category_id'])->count();
        if($category_count < 1){
            throw new DbException('添加失败:分类不存在',50001);
        }

        try {

            $result = HelpModel::where('id','=',$id)->update($data);
            if(!$result){
                throw new DbException('更新失败',50001);
            }

            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


    /**
     * 帮助删除
     *
     * @return Responses
     * */
    public function delete()
    {

        //表单验证
        $validate = new HelpValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        try {

            HelpModel::where('id','=',$id)->delete();
            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


}
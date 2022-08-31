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
use app\common\model\ActivityModel;
use app\common\model\ActivityCategoryModel;
use app\admin\validate\ActivityCategoryValidate;
use think\facade\Request;
use think\facade\View;
use Tools\Responses;

class ActivityCategoryController
{

    use AuthTrait;

    protected $directory = 'activity_category';

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

        $ActivityCategoryModel = new ActivityCategoryModel();

        //处理过滤
        $filters = Request::post('filters',[]);

        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $ActivityCategoryModel = $ActivityCategoryModel->where('id','in',$value);
                        }
                        break;
                    case 'search_text':
                        $value = htmlspecialchars(strip_tags(trim($value)));
                        if($value){
                            $ActivityCategoryModel = $ActivityCategoryModel->whereLike('title', $value.'%');
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $ActivityCategoryModel
            ->order('sort DESC,id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = [];
        if($lists['data']){
            foreach ($lists['data'] as $data){
                $data['min_price'] = fen_to_float($data['min_price']);
                $datas[] = $data;
            }
        }



        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

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
     * 添加分类
     *
     * @return Responses
     * */
    public function create()
    {

        //表单验证
        $validate = new ActivityCategoryValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $data = [];
        $data['title'] = Request::post('title','','trim,strip_tags,htmlspecialchars');
        $data['icon'] = Request::post('icon','','trim');
        $data['describe'] = Request::post('describe','','trim,strip_tags,htmlspecialchars');
        $data['status'] = Request::post('status',2,'intval') == 1 ? 1 : 2;
        $data['min_price'] = fen_to_int(Request::post('min_price',0,'floatval'));
        $data['min_number'] = Request::post('min_number',0,'intval');
        $data['sort'] = Request::post('sort',0,'intval,abs');

        try {

            $result = ActivityCategoryModel::create($data);
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
        $validate = new ActivityCategoryValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = ActivityCategoryModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '分类不存在',40401 );
        }

        $data = (string)View::fetch($this->directory . '/edit',compact('info'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 编辑分类
     *
     * @return Responses
     * */
    public function update()
    {

        //表单验证
        $validate = new ActivityCategoryValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = ActivityCategoryModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '分类不存在',40401 );
        }

        $data = [];
        $data['title'] = Request::post('title','','trim,strip_tags,htmlspecialchars');
        $data['icon'] = Request::post('icon','','trim');
        $data['describe'] = Request::post('describe','','trim,strip_tags,htmlspecialchars');
        $data['min_price'] = fen_to_int(Request::post('min_price',0,'floatval'));
        $data['min_number'] = Request::post('min_number',0,'intval');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['status'] = Request::post('status',2,'intval') == 1 ? 1 : 2;
        $data['sort'] = Request::post('sort',0,'intval,abs');

        try {

            $result = ActivityCategoryModel::where('id','=',$id)->update($data);
            if(!$result){
                throw new DbException('更新失败',50001);
            }

            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


    /**
     * 分类删除
     *
     * @return Responses
     * */
    public function delete()
    {

        //表单验证
        $validate = new ActivityCategoryValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $activityCount = ActivityModel::where('category_id','=',$id)->count();
        if($activityCount > 0 ){
            return Responses::data(40401, '不可以删除有活动内容的分类');
        }

        try {

            ActivityCategoryModel::where('id','=',$id)->delete();
            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


}
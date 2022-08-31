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
use app\common\model\AdminAccessModel;
use app\common\model\AdminModel;
use app\common\model\AdminNodeModel;
use app\common\model\AdminRoleModel;
use app\admin\validate\AdminRoleValidate;
use think\Exception;
use think\facade\Request;
use think\facade\View;
use Tools\Responses;

class AdminRoleController
{

    use AuthTrait;

    protected $directory = 'admin_role';

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

        $AdminRoleModel = new AdminRoleModel();

        //处理过滤
        $filters = Request::post('filters',[]);

        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $AdminRoleModel = $AdminRoleModel->where('id','in',$value);
                        }
                        break;
                    case 'search_text':
                        $value = htmlspecialchars(strip_tags(trim($value)));
                        if($value){
                            $AdminRoleModel = $AdminRoleModel->whereLike('title', $value.'%');
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $AdminRoleModel
            ->order('id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = $lists['data'];

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
     * 添加角色
     *
     * @return Responses
     * */
    public function create()
    {

        //表单验证
        $validate = new AdminRoleValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $data = [];
        $data['title'] = Request::post('title','','trim,strip_tags,htmlspecialchars');
        $data['is_system'] = 2;

        try {

            $result = AdminRoleModel::create($data);
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
        $validate = new AdminRoleValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = AdminRoleModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '角色不存在',40401 );
        }

        $data = (string)View::fetch($this->directory . '/edit',compact('info'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 编辑角色
     *
     * @return Responses
     * */
    public function update()
    {

        //表单验证
        $validate = new AdminRoleValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = AdminRoleModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '角色不存在',40401 );
        }

        $data = [];
        $data['title'] = Request::post('title','','trim,strip_tags,htmlspecialchars');
        $data['updated_at'] = date('Y-m-d H:i:s');

        try {

            $result = AdminRoleModel::where('id','=',$id)->update($data);
            if(!$result){
                throw new DbException('更新失败',50001);
            }

            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }







    /**
     * 权限模板
     * */
    public function access_view()
    {

        $validate = new AdminRoleValidate();

        if (!$validate->scene(__FUNCTION__)->check(Request::post())) {
            throw new Exception($validate->getError(),40004);
        }

        $role_id = Request::post('role_id');

        $role = AdminRoleModel::where('id','=',$role_id)
            ->find();


        if(!$role){
            throw new Exception('角色不存在或已被删除',40400);
        }

        $nodeList = AdminNodeModel::where('parent_id','=',0)
            ->where('type','=',1)
            ->where('status','=',1)
            ->select();


        $list = [];
        if($nodeList){
            foreach ($nodeList as $node){

                $node->is_choose = (bool)AdminAccessModel::where('role_id','=',$role_id)->where('node_id','=',$node->id)->count();

                $child_nodes = AdminNodeModel::where('parent_id','=',$node->id)
                    ->where('type','=',2)
                    ->where('status','=',1)
                    ->select();

                if($child_nodes){
                    $node_child_nodes = [];
                    foreach ($child_nodes as $tow){

                        $tow->is_choose = (bool)AdminAccessModel::where('role_id','=',$role_id)->where('node_id','=',$tow->id)->count();
                        $node_child_nodes[] = $tow;

                    }

                }
                $node['child_nodes'] = $node_child_nodes;
                $list[] = $node;

            }
        }

        $data = (string)View::fetch('admin_role/access', compact('role','list'));
        return Responses::data(200, 'success',$data);

    }



    /**
     * 权限数据
     * */
    public function access()
    {

        $validate = new AdminRoleValidate();

        if (!$validate->scene(__FUNCTION__)->check(Request::post())) {
            throw new Exception($validate->getError(),40004);
        }

        $role_id = Request::post('role_id');
        $role = AdminRoleModel::where('id','=',$role_id)
            ->find();

        if(!$role){
            throw new Exception('角色不存在或已被删除',40400);
        }

        $node_ids = Request::post('node_ids',[]);

        try{

            AdminAccessModel::where('role_id','=',$role_id)->delete();

            if($node_ids && is_array($node_ids)){

                foreach ($node_ids as $node_id){
                    AdminAccessModel::create([
                        'role_id'=>$role_id,
                        'node_id'=>$node_id,
                    ]);
                }
            }

            adminRoleUpdataVersion($role_id,time());
            return Responses::data(200, 'success');
        }catch (Exception $e){
            throw new Exception($e->getMessage(),50002);
        }

    }



    /**
     * 角色删除
     *
     * @return Responses
     * */
    public function delete()
    {

        //表单验证
        $validate = new AdminRoleValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = AdminRoleModel::where('id','=',$id)
            ->find();

        if(!$info){
            return Responses::data(40401, '角色已不存在');
        }else if($info['is_system'] == 1){
            return Responses::data(40401, '不可以删除系统角色');
        }

        $adminCount = AdminModel::where('role_id','=',$id)->count();
        if($adminCount > 0 ){
            return Responses::data(40401, '不可以删除有平台账号的角色');
        }

        try {

            AdminRoleModel::where('id','=',$id)->delete();
            AdminAccessModel::where('role_id','=',$id)->delete();
            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


}
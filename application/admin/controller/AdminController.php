<?php
/**
 * AdminController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/15
 */

namespace app\admin\controller;


use app\admin\traits\AuthTrait;
use app\admin\validate\AdminValidate;
use app\common\exception\DbException;
use app\common\exception\RequestException;
use app\common\model\AdminModel;
use app\common\model\AdminRoleModel;
use app\common\model\UserExtModel;
use app\common\model\UserModel;
use think\facade\Request;
use think\facade\View;
use Tools\Responses;

class AdminController
{

    use AuthTrait;

    protected $directory = 'admin';

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

        $AdminModel = new AdminModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $AdminModel = $AdminModel->where('id','in',$value);
                        }
                        break;
                    case 'search_text':
                        $value = htmlspecialchars(strip_tags(trim($value)));
                        if($value){
                            $user_ids = UserModel::where("(true_name='{$value}' OR user_name='{$value}' OR nick_name='{$value}' OR phone='{$value}' OR email='{$value}')")->column('id','id');
                            if($user_ids){
                                if(is_array($user_ids)){
                                    $AdminModel = $AdminModel->where('user_id','in',$user_ids);
                                }else{
                                    $AdminModel = $AdminModel->where('user_id','=',$user_ids);
                                }
                            }else{
                                $AdminModel = $AdminModel->where('user_id','=','0');
                            }
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $AdminModel
            ->with(['user','role'])
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
        $validate = new AdminValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::param());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = AdminModel::where('id','=',$id)->with(['user','role'])->find();
        if(!$info){
            throw new RequestException( '管理员不存在',40401);
        }

        $info['ext'] = UserExtModel::where('user_id','=',$info['user_id'])->find();
        $data = (string)View::fetch($this->directory . '/detail',compact('info'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 创建模板
     *
     * @return Responses
     * */
    public function createView()
    {
        $roles = AdminRoleModel::select();
        $data = (string)View::fetch($this->directory . '/add',compact('roles'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 创建数据
     *
     * @return Responses
     * */
    public function create()
    {

        //表单验证
        $validate = new AdminValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $data = [];
        $data['user_id'] = Request::post('user_id',0,'intval');
        $data['role_id'] = Request::post('role_id',0,'intval');
        $data['status'] = 100;

        //用户已经是管理员
        $adminCount = AdminModel::where('user_id','=',$data['user_id'])->count();
        if($adminCount >= 1){
            throw new DbException('关联的用户已经是管理员',40003);
        }

        //只能有一个超级管理员
        if($data['role_id'] == 1){
            throw new DbException('只能添加一个超级管理员',40003);
        }

        try {

            $admin = AdminModel::create($data);
            if(!$admin){
                throw new DbException('数据失败',50001);
            }

            UserModel::where('id','=',$admin['user_id'])->update([
               'is_admin'=>100,
               'admin_id'=>$admin['id'],
            ]);

            return Responses::data(200, 'success',['id'=>$admin['id']]);

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


    /**
     * 更新数据
     *
     * @return Responses
     * */
    public function updateView()
    {

        //表单验证
        $validate = new AdminValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::param());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = AdminModel::where('id','=',$id)->with('user')->find();
        if(!$info){
            throw new RequestException( '数据不存在',40401 );
        }

        $roles = AdminRoleModel::select();
        $data = (string)View::fetch($this->directory . '/edit',compact('roles','info'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 更新数据
     *
     * @return Responses
     * */
    public function update()
    {

        //表单验证
        $validate = new AdminValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = UserModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '数据不存在',40401 );
        }

        $data = [];
        $data['role_id'] = Request::post('role_id',0,'intval');
        $data['updated_at'] = date('Y-m-d H:i:s');

        //只能有一个超级管理员
        if($data['role_id'] == 1){
            throw new DbException('只能设置一个超级管理员',40003);
        }

        try {

            $result = AdminModel::where('id','=',$id)->update($data);
            if(!$result){
                throw new DbException('更新失败',50001);
            }

            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


    /**
     * 设置状态
     *
     * @return Responses
     * */
    public function switch()
    {

        //表单验证
        $validate = new AdminValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = UserModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '数据不存在',40401 );
        }

        $data = [];
        $data['status'] = Request::post('status',2,'intval');
        $data['updated_at'] = date('Y-m-d H:i:s');

        try {

            $result = UserModel::where('id','=',$id)->update($data);
            if(!$result){
                throw new DbException('更新失败',50001);
            }

            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }




    /**
     * 删除权限
     *
     * @return Responses
     * */
    public function delete()
    {

        //表单验证
        $validate = new AdminValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = AdminModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '管理员不存在',40401 );
        }

        if($info['role_id'] == 1){
            throw new RequestException( '不可以删除超级管理员的权限',40401 );
        }

        try {

            AdminModel::where('id','=',$id)->delete();
            UserModel::where('id','=',$id)->update([
                'is_admin'=>2,
                'admin_id'=>0,
                'updated_at'=>date('Y-m-d H:i:s')
            ]);

            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


}
<?php


namespace app\install\controller;


use app\common\model\ConfigModel;
use app\install\validate\InstallValidate;
use app\common\exception\RequestException;
use think\Db;
use think\facade\Request;
use Tools\Responses;

class IndexController
{


    public function index(){

        $is_install = (bool)file_exists(env('root_path').'data/install._lock');
        return view('index/index',compact('is_install'));

    }


    public function create(){

        set_time_limit(0);

        //更新配置
        try{

            $is_install = file_exists(env('root_path').'data/install._lock');
            if($is_install){
                throw new \Exception('管理员账户已被注册',500);
            }

            $datas = [];

            //表单验证
            $validate = new InstallValidate();
            $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
            if(!$vResult){
                throw new RequestException( $validate->getError(),40003);
            }

            //网站信息
            $datas['site.name'] = Request::post('site_name','','trim');
            $datas['site.domain'] = rtrim(Request::post('site_domain','','trim'),'/');

            //数据库信息
            $datas['db.type'] = 'mysql';
            $datas['db.hostname'] = Request::post('db_hostname','','trim');
            $datas['db.hostport'] = Request::post('db_hostport','','trim');
            $datas['db.database'] = Request::post('db_database','','trim');
            $datas['db.username'] = Request::post('db_username','','trim');
            $datas['db.password'] = Request::post('db_password','','trim');

            //缓存信息
            $datas['cache.driver'] = 'redis';
            $datas['cache.host'] = Request::post('cache_host','','trim');
            $datas['cache.port'] = Request::post('cache_port','','trim');
            $datas['cache.password'] = Request::post('cache_password','','trim');

            //公众号
            $datas['mobile_mp.switch'] = 1;
            $datas['mobile_mp.link'] = rtrim(Request::post('mobile_mp_link','','trim'),'/');
            $datas['mobile_mp.app_id'] = Request::post('mobile_mp_app_id','','trim');
            $datas['mobile_mp.app_secret'] = Request::post('mobile_mp_app_secret','','trim');

            $envs = [];
            $envs['SITE_NAME'] = Request::post('site_name','','trim');
            $envs['SITE_DOMAIN'] = rtrim(Request::post('site_domain','','trim'),'/');
            $envs['DB_TYPE'] = 'mysql';
            $envs['DB_HOSTNAME'] = Request::post('db_hostname','','trim');
            $envs['DB_DATABASE'] = $datas['db.database'];
            $envs['DB_USERNAME'] = $datas['db.username'];
            $envs['DB_PASSWORD'] = $datas['db.password'];
            $envs['DB_HOSTPORT'] = $datas['db.hostport'];
            $envs['DB_CHARSET'] = "utf8mb4";

            $envs['CACHE_DRIVER'] = $datas['cache.driver'];
            $envs['CACHE_HOST'] = $datas['cache.host'];
            $envs['CACHE_PORT'] = $datas['cache.port'];
            $envs['CACHE_EXPIRE'] = 604800;

            modEnv(env('root_path'),$envs);



            //创建用户
            $user = [];
            $user['invitation_code'] = get_invitation_code(1001);
            $user['user_name'] = Request::post('admin_user_name','','trim');
            $user['password'] = password_encrypt(Request::post('admin_password','','trim'));
            $user['nick_name'] = $user['user_name'];
            $user['true_name'] = $user['user_name'];
            $user['avatar'] = 'https://static.kechuang.link/task/static/images/default/user.png';
            $user['is_wechat'] = 2;
            $user['status'] = 1;
            $user['is_admin'] = 100;
            $user['admin_id'] = 1001;
            $user['created_at'] = date('Y-m-d H:i:s');
            $user['updated_at'] = date('Y-m-d H:i:s');

            $user = Db::connect()->table('users')->insert($user);
            if(!$user){
                throw new \Exception('管理员账户添加失败',500);
            }

            $user_ext = [];
            $user_ext['user_id'] = 1001;
            $user_ext['created_at'] = date('Y-m-d H:i:s');
            $user_ext['updated_at'] = date('Y-m-d H:i:s');
            $userExt = Db::connect()->table('user_exts')->insert($user_ext);
            if(!$userExt){
                throw new \Exception('管理员账户添加失败',500);
            }

            $admin = [];
            $admin['role_id'] = 1;
            $admin['user_id'] = 1001;
            $admin['status'] = 100;
            $admin['created_at'] = date('Y-m-d H:i:s');
            $admin['updated_at'] = date('Y-m-d H:i:s');

            $admin = Db::connect()->table('admins')->insert($admin);
            if(!$admin){
                throw new \Exception('管理员账户添加失败',500);
            }


            $configs = [];
            $updated_at = date('Y-m-d H:i:s');
            foreach ($datas as $name=>$val){

                $data = [];
                $data['content'] = $val;
                $data['updated_at'] = $updated_at;

                $configs[] = Db::connect()->table('configs')->where('name','=',$name)->update($data);
            }

            //证书
            $wx_certificate_dir = env('root_path').'data/wxpay/cert/';
            file_put_contents($wx_certificate_dir.'cert_client.pem','');
            file_put_contents($wx_certificate_dir.'cert_key.pem','');

            file_put_contents(env('root_path').'data/install._lock',time());
            return Responses::data(200,'success');

        }catch (\Exception $e){
            return Responses::data(50001,$e->getMessage());
        }

    }

}
<?php
/**
 * UserController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/9
 */

namespace app\admin\controller;


use app\admin\traits\AuthTrait;
use app\admin\validate\StockGiveValidate;
use app\admin\validate\UserBalanceUpdateValidate;
use app\common\exception\DbException;
use app\common\exception\RequestException;
use app\common\model\ActivityAddToModel;
use app\common\model\ActivityModel;
use app\common\model\ActivityRecModel;
use app\common\model\BigAgencyModel;
use app\common\model\DistrictAgencyModel;
use app\common\model\OperatorModel;
use app\common\model\OrderModel;
use app\common\model\ResumeModel;
use app\common\model\StockModel;
use app\common\model\UserExtModel;
use app\common\model\UserModel;
use app\admin\validate\UserValidate;
use app\common\model\WalletModel;
use Carbon\Carbon;
use think\Db;
use think\Exception;
use think\facade\Request;
use think\facade\View;
use Tools\Responses;

class UserController
{

    use AuthTrait;

    protected $directory = 'user';

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

        $UserModel = new UserModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $UserModel = $UserModel->where('id','in',$value);
                        }
                        break;
                    case 'certification_status':
                        $value = intval($value);
                        if($value){
                            $UserModel = $UserModel->where('certification_status','=',$value);
                        }
                        break;
                    case 'search_text':
                        $value = htmlspecialchars(strip_tags(trim($value)));
                        if($value){
                            $UserModel = $UserModel->where("(ID='{$value}' OR true_name='{$value}' OR user_name='{$value}' OR nick_name='{$value}' OR phone='{$value}' OR email='{$value}')");
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $UserModel
            ->order('id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = $lists['data'];

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }




    /**
     * 数据列表
     *
     * @return Responses
     * */
    public function search()
    {

        $UserModel = new UserModel();

        //处理过滤
        $search_text = Request::post('search_text','','trim,strip_tags,htmlspecialchars');
        if($search_text){
            $UserModel = $UserModel->where("(true_name LIKE '{$search_text}%' OR user_name LIKE '{$search_text}%' OR nick_name LIKE '{$search_text}%' OR phone LIKE '{$search_text}%')");
        }

        $is_admin = Request::param('is_admin',0,'intval');
        if($is_admin){
            $UserModel = $UserModel->where('is_admin','=',$is_admin);
        }

        $page_size = Request::post('page_size',env('page_size',100));

        $lists = $UserModel
            ->field('id,nick_name,true_name,phone')
            ->order('id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = [];
        if($lists['data']){
            foreach ($lists['data'] as $data){
                $datas[] = [
                  'id'=>$data['id'],
                  'name'=>$data['nick_name'] . ($data['true_name'] ? '('.$data['true_name'].')' : '') . ($data['phone'] ? '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$data['phone'] : '')
                ];
            }
        }

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
        $validate = new UserValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::param());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = UserModel::where('id','=',$id)->with(['ext'])->find();
        if(!$info){
            throw new RequestException( '用户不存在',40401);
        }

        $info['parent'] = [];
        if($info['parent_id'] >= 1){
            $info['parent'] = UserModel::where('id','=',$info['parent_id'])->field('user_name,true_name,phone')->find();
        }


        $data = (string)View::fetch($this->directory . '/detail',compact('info'));
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
        $validate = new UserValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $data = [];
        $data['title'] = Request::post('title','','trim');
        $data['sort'] = Request::post('sort',0,'intval');
        $data['status'] = 2;

        try {

            $info = UserModel::create($data);
            if(!$info){
                throw new DbException('数据添加失败',50001);
            }

            return Responses::data(200, 'success',['id'=>$info->id]);

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


    /**
     * 更新数据
     *
     * @return Responses
     * */
    public function update()
    {

        //表单验证
        $validate = new UserValidate();
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
        $data['title'] = Request::post('title','','trim');
        $data['sort'] = Request::post('sort',0,'intval');

        try {

            $result = UserModel::where('id','=',$id)->update($data);
            if(!$result){
                throw new DbException('数据更新失败',50001);
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
        $validate = new UserValidate();
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
     * 设置状态
     *
     * @return Responses
     * */
    public function certification_audit_view()
    {

        //表单验证
        $validate = new UserValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::param());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::param('id');
        $info = UserModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '数据不存在',40401 );
        }

        $data = (string)View::fetch($this->directory . '/certification_audit',compact('info'));
        return Responses::data(200, 'success',$data);
    }


    /**
     * 设置状态
     *
     * @return Responses
     * */
    public function certification_audit()
    {

        //表单验证
        $validate = new UserValidate();
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
        $data['certification_status'] = Request::post('certification_status',3,'intval');
        $reasons = Request::post('reasons','','trim,strip_tags,htmlspecialchars');
        $data['updated_at'] = date('Y-m-d H:i:s');

        if($info['certification_status'] != 1){
            throw new RequestException( '审核失败：只能审核待审核的认证',40401 );
        }

        try {

            $result = UserModel::where('id','=',$id)->update($data);
            if(!$result){
                throw new DbException('更新失败',50001);
            }

            $data['certification_reason'] = $data['certification_status'] == 100 ? '' : $reasons;
            UserExtModel::where('user_id','=',$id)->update($data);

            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


    /**
     * 设置代理等级
     *
     * @return Responses
     * */
    public function vip_update_view()
    {

        //表单验证
        $validate = new UserValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::param());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::param('id');
        $info = UserModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '数据不存在',40401 );
        }

        $data = (string)View::fetch($this->directory . '/vip_update',compact('info'));
        return Responses::data(200, 'success',$data,compact('info'));
    }


    /**
     * 设置会员等级
     *
     * @return Responses
     * */
    public function vip_update()
    {

        //表单验证
        $validate = new UserValidate();
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
        $data['user_level'] = Request::post('user_level',0,'intval');
        $data['user_level_edate'] = NULL;
        if($data['user_level'] == 1){
            $data['user_level_edate'] = Request::post('user_level_edate',NULL,'trim');
            $data['user_level_edate'] = date('Y-m-d',strtotime($data['user_level_edate']));
        }

        $data['merchant_level'] = Request::post('merchant_level',0,'intval');
        $data['merchant_level_edate'] = NULL;
        if($data['merchant_level'] == 1){
            $data['merchant_level_edate'] = Request::post('merchant_level_edate',NULL,'trim');
            $data['merchant_level_edate'] = date('Y-m-d',strtotime($data['merchant_level_edate']));
        }

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
     * 设置余额
     *
     * @return Responses
     * */
    public function balance_update_view()
    {

        //表单验证
        $validate = new UserValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::param());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::param('id');
        $info = UserModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '用户不存在',40401 );
        }

        $data = (string)View::fetch($this->directory . '/balance_update',compact('info'));
        return Responses::data(200, 'success',$data);
    }


    /**
     * 设置押金
     *
     * @return Responses
     * */
    public function balance_update()
    {

        //表单验证
        $validate = new UserBalanceUpdateValidate();
        $vResult = $validate->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = UserModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '用户不存在',40401 );
        }

        $set_type = Request::post('set_type',1,'intval') == 1 ? 1 : 2;
        $money = fen_to_int(Request::post('money',0,'floatval'));
        $describe = Request::post('describe','','trim,strip_tags,htmlspecialchars');

        if($set_type == 1){
            //写入充值
            $wallet = WalletModel::create([
                'type'=>1,
                'trade_no'=>get_trade_no(),
                'user_id'=>$id,
                'category'=>1001,//平台赠送
                'money'=>$money,
                'actual_amount'=>$money,
                'status'=>100,
                'describe'=>$describe,
                'finish_dt'=>date('Y-m-d H:i:s',time()),
            ]);

            if(!$wallet){
                throw new Exception('赠送失败',50001);
            }

            //重新统计余额
            UserModel::resetTotalAccount($id);
            return Responses::data(200, 'success',['id'=>$wallet['id']]);
        }else{

            //加锁扣除
            $lock_file = env('root_path').'locks/user_account_'.$id.'_lock.txt';

            $file = fopen($lock_file,"w+");

            //锁定
            if(flock($file,LOCK_EX)){

                Db::startTrans();

                try{

                    //获取余额
                    $balance = UserModel::getBalanceTotal($id);
                    if($balance < $money){
                        throw new Exception('扣除失败：账户余额不足！',40003);
                    }

                    //写入扣款
                    $wallet = WalletModel::create([
                        'type'=>2,
                        'trade_no'=>get_trade_no(),
                        'user_id'=>$id,
                        'category'=>2001,//平台扣除
                        'money'=>$money,
                        'actual_amount'=>$money,
                        'status'=>100,
                        'describe'=>$describe,
                        'finish_dt'=>date('Y-m-d H:i:s',time()),
                    ]);

                    if(!$wallet){
                        throw new Exception('扣款失败',50001);
                    }

                    //重新统计余额
                    UserModel::resetTotalAccount($id);

                    Db::commit();
                    //解锁
                    flock($file,LOCK_UN);
                    //关闭文件
                    fclose($file);

                    return Responses::data(200, 'success',['id'=>$wallet['id']]);
                }catch (\Exception $e){
                    Db::rollback();
                    //解锁
                    flock($file,LOCK_UN);
                    //关闭文件
                    fclose($file);
                    return Responses::data(50001, $e->getMessage());
                }

            }

        }

    }



    /**
     * 设置余额
     *
     * @return Responses
     * */
    public function deposit_update_view()
    {

        //表单验证
        $validate = new UserValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::param());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::param('id');
        $info = UserModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '用户不存在',40401 );
        }

        $data = (string)View::fetch($this->directory . '/deposit_update',compact('info'));
        return Responses::data(200, 'success',$data);
    }


    /**
     * 设置押金
     *
     * @return Responses
     * */
    public function deposit_update()
    {

        //表单验证
        $validate = new UserBalanceUpdateValidate();
        $vResult = $validate->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = UserModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '用户不存在',40401 );
        }

        $set_type = Request::post('set_type',1,'intval') == 1 ? 1 : 2;
        $money = fen_to_int(Request::post('money',0,'floatval'));

        if($set_type == 1){

            $result = UserModel::where('id','=',$id)->update([
                'deposit'=>Db::raw('deposit+'.$money),
                'updated_at'=>date('Y-m-d H:i:s',time()),
            ]);

            if(!$result){
                throw new RequestException( '操作失败',40401 );
            }

            return Responses::data(200, 'success');

        }else{

            //加锁扣除
            $lock_file = env('root_path').'locks/user_deposit_'.$id.'_lock.txt';

            $file = fopen($lock_file,"w+");

            //锁定
            if(flock($file,LOCK_EX)){

                $deposit = UserModel::where('id','=',$id)->value('deposit');

                try{

                    if($deposit < $money){
                        throw new Exception('扣除失败：账户押金不足！',40003);
                    }

                    $result = UserModel::where('id','=',$id)->where('deposit','>=',$money)->update([
                        'deposit'=>Db::raw('deposit-'.$money),
                        'updated_at'=>date('Y-m-d H:i:s',time()),
                    ]);

                    if(!$result){
                        throw new Exception('扣款失败:账户押金不足',50001);
                    }

                    //解锁
                    flock($file,LOCK_UN);
                    //关闭文件
                    fclose($file);

                    return Responses::data(200, 'success');
                }catch (\Exception $e){

                    //解锁
                    flock($file,LOCK_UN);
                    //关闭文件
                    fclose($file);
                    return Responses::data(50001, $e->getMessage());
                }

            }

        }

    }



}
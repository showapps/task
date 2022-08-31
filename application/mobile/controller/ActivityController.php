<?php


namespace app\mobile\controller;


use app\common\exception\DbException;
use app\common\exception\Exception;
use app\common\exception\RequestException;
use app\common\model\ActivityAddToModel;
use app\common\model\ActivityAuditModel;
use app\common\model\ActivityCategoryModel;
use app\common\model\ActivityModel;
use app\common\model\ActivityRecModel;
use app\common\model\ActivityRefreshModel;
use app\common\model\ActivityStepModel;
use app\common\model\OrderModel;
use app\common\model\TaskModel;
use app\common\model\UserModel;
use app\mobile\traits\AuthTrait;
use app\mobile\traits\ModuleTrait;
use app\mobile\validate\ActivityAddToValidate;
use app\mobile\validate\ActivityLogValidate;
use app\mobile\validate\ActivitySaveValidate;
use app\mobile\validate\ActivityValidate;
use think\Db;
use think\facade\Request;
use Tools\Auth;
use Tools\Responses;

class ActivityController
{

    use AuthTrait;
    use ModuleTrait;


    /**
     * 活动列表
     *
     * @return Responses
     * */
    public function lists()
    {

        $user_id = 0;
        $token = Request::post('token','');
        if($token){
            if(Auth::guard(self::$module_name)->token($token)->check()){
                $this->initAuthInfo($token);
                $user_id = self::$user_id;
            }
        }

        $ActivityModel = new ActivityModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'id':
                        $value = (int)$value;
                        if($value){
                            $ActivityModel = $ActivityModel->where('id','=',$value);
                        }
                        break;
                    case 'ids':
                        if($value && is_array($value)){
                            $ActivityModel = $ActivityModel->where('id','in',$value);
                        }
                        break;
                    case 'category_id':
                        $value = (int)$value;
                        if($value){
                            $ActivityModel = $ActivityModel->where('category_id','=',$value);
                        }
                        break;
                    case 'title':
                        $value = trim(htmlspecialchars($value));
                        if($value){
                            $ActivityModel = $ActivityModel->where('title','LIKE','%'.$value.'%');
                        }
                        break;
                    case 'merchant_id':
                        $value = (int)ltrim($value,'R');
                        if($value){
                            $ActivityModel = $ActivityModel->where('merchant_id','=',$value);
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $ActivityModel = $ActivityModel->where('status','in',$value);
                        }
                        break;
                    case 'in_recommend':
                        if($value == 1){
                            $ActivityModel = $ActivityModel->whereTime('rec_end_dt','>',date('Y-m-d H:i:s'));
                        }
                        break;
                }
            }
        }

        $orders = Request::post('orders',[]);
        if((!$orders) || (!is_array($orders))){
            $orders = [['default','desc' ]];
        }

        $orderString = '';
        if($orders && is_array($orders)){
            $orderList = [];
            foreach ($orders as $row){
                if(isset($row[0]) && isset($row[1]) && in_array($row[1],['asc','desc'])){
                    switch ($row[0]){
                        case 'default':
                            $orderList[] = 'refresh_time '.$row[1];
                            break;
                        case 'id':
                            $orderList[] = 'id '.$row[1];
                            break;
                        case 'price':
                            $orderList[] = 'price '.$row[1];
                            break;
                    }
                }
            }
            $orderString = implode(',',$orderList);
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $ActivityModel
            ->with([
                'category'=>function($query){
                    $query->field('id,title');
                },
                'merchant'=>function($query){
                    $query->field('id,nick_name,true_name,avatar');
                }
            ])
            ->order($orderString)
            ->paginate($page_size)
            ->toArray();

        $includes = Request::post('includes',[]);
        $datas = [];
        if($lists['data']){

            //获取活动佣金
            $task_commission_rate = dbConfig('commission.task_rate',0);
            $task_vip_commission_rate = dbConfig('commission.task_vip_rate',0);

            foreach ($lists['data'] as $data){
                $data['price'] = fen_to_float($data['price']);
                $data['task'] = [];

                //计算佣金
                $data['task_commission'] = get_task_commission($data['price'],$task_commission_rate);
                $data['task_vip_commission'] = get_task_commission($data['price'],$task_vip_commission_rate);

                //默认普通佣金
                $data['commission'] = $data['task_commission'];

                //已登录
                if($user_id){
                    //用户是会员
                    $levels = [self::$user['user_level'],self::$user['merchant_level']];
                    if(in_array(1,$levels)){
                        $data['commission'] = $data['task_vip_commission'];
                    }
                }

                //用户已登录
                if($user_id && in_array('task',$includes)){
                    $data['task'] = TaskModel::where('activity_id','=',$data['id'])
                        ->where('user_id','=',$user_id)
                        ->order('id DESC')
                        ->find();
                }

                $datas[] = $data;
            }
        }

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


    /**
     * 分类列表
     *
     * @return Responses
     * */
    public function categorys()
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
                    case 'status':
                        if($value && is_array($value)){
                            $ActivityCategoryModel = $ActivityCategoryModel->where('status','in',$value);
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
        if ($lists['data']){
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
     * 活动详情
     *
     * @return Responses
     * */
    public function detail()
    {

        //表单验证
        $validate = new ActivityValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = ActivityModel::where('id','=',$id)
            ->with([
                'category'=>function($query){
                    $query->field('id,title,min_price,min_number');
                },
                'merchant'=>function($query){
                    $query->field('id,nick_name,true_name,avatar,certification_status,deposit,merchant_level');
                },
                'steps'=>function($query){
                    $query->field('id,activity_id,type,image,describe');
                }
            ])
            ->find();
        if(!$info){
            throw new RequestException( '活动不存在',40402);
        }

        $info['price'] = fen_to_float($info['price']);
        $info['category']['min_price'] = fen_to_float($info['category']['min_price']);

        //获取活动佣金
        $task_commission_rate = dbConfig('commission.task_rate',0);
        $task_vip_commission_rate = dbConfig('commission.task_vip_rate',0);

        //计算佣金
        $info['task_commission'] = get_task_commission($info['price'],$task_commission_rate);
        $info['task_vip_commission'] = get_task_vip_commission($info['price'],$task_vip_commission_rate);

        //默认普通佣金
        $info['commission'] = $info['task_commission'];

        $user_id = 0;
        $token = Request::post('token','');
        if($token){
            if(Auth::guard(self::$module_name)->token($token)->check()){
                $this->initAuthInfo($token);
                $user_id = self::$user_id;
            }
        }

        //已登录
        if($user_id){
            //用户是会员
            $levels = [self::$user['user_level'],self::$user['merchant_level']];
            if(in_array(1,$levels)){
                $info['commission'] = $info['task_vip_commission'];
            }
        }

        $includes = Request::post('includes',[]);
        $info['task'] = [];
        //用户已登录
        if($user_id && in_array('task',$includes)){

            $info['task'] = TaskModel::where('activity_id','=',$id)
                ->where('user_id','=',$user_id)
                ->with([
                    'task_steps'=>function($query){
                        $query->field('id,task_id,step_id,type,num_type,image,sub_image,describe');
                        $query->where('type','=',2);
                    }
                ])
                ->order('id DESC')
                ->find();
        }

        //管理日志统计
        if(in_array('audit_total',$includes)){
            $info['audit_total'] = ActivityAuditModel::where('activity_id','=',$id)->count();
        }

        if(in_array('addto_total',$includes)){
            $info['addto_total'] = ActivityAddToModel::where('activity_id','=',$id)
                ->where('status','=',100)
                ->count();
        }

        if(in_array('refresh_total',$includes)){
            $info['refresh_total'] = ActivityRefreshModel::where('activity_id','=',$id)
                ->count();
        }

        if(in_array('recommend_total',$includes)){
            $info['recommend_total'] = ActivityRecModel::where('activity_id','=',$id)
                ->where('status','=',100)
                ->count();
        }

        //用户倒计时
        if(in_array('user_end_date',$includes) && $info['task']){

            //提交倒计时
            if($info['task']['status'] == 1){
                $info['task']['end_info'] = get_end_info($info['task']['submit_end_dt']);
            }

            //复审倒计时
            if($info['task']['status'] == 3){
                $info['task']['end_info'] = get_end_info($info['task']['recheck_submit_end_dt']);
            }

            //举报倒计时
            if($info['task']['status'] == 5 && $info['task']['recheck_status'] == 3){
                $info['task']['end_info'] = get_end_info($info['task']['report_open_end_dt']);
            }

            //辩论倒计时
            if($info['task']['status'] == 6 && $info['task']['report_status'] == 1){
                $info['task']['end_info'] = get_end_info($info['task']['report_bl_end_dt']);
            }
        }

        return Responses::data(200, 'success',$info);

    }


    /**
     * 商家统计
     *
     * @return Responses
     * */
    public function merchant_total()
    {

        $this->initAuthInfo();

        $datas = [];
        $datas[0] = ActivityModel::where('merchant_id','=',self::$user_id)
            ->where('status','=',3)
            ->count();

        return Responses::data(200, 'success',$datas);

    }


    /**
     * 活动创建
     *
     * @return Responses
     * */
    public function create()
    {

        $this->initAuthInfo();

        $datas = Request::post();
        $category_id = Request::post('category_id',0,'intval');
        $datas['category'] = ActivityCategoryModel::where('id','=',$category_id)->find();


        //未实名认证
        if(self::$user['certification_status'] != 100){
            throw new RequestException( '发布失败：请先通过实名认证',40003);
        }

        //禁止发布活动
        if(in_black(self::$user_id,1004)){
            throw new RequestException('平台禁止您发布活动，请联系客服解冻',40003);
        }

        //不是超级商人
        if(self::$user['merchant_level'] != 1 ){
            //最多只能发布2个活动
            $activityTotal = ActivityModel::where('merchant_id','=',self::$user_id)->count();
            if($activityTotal >= 2){
                throw new RequestException('发布失败：不是超级商人最多只能发布2个活动',40003);
            }
        }

        //表单验证
        $validate = new ActivitySaveValidate();
        $vResult = $validate->scene(__FUNCTION__)->check($datas);
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $data = [];
        $data['title'] = Request::post('title','','trim,strip_tags,htmlspecialchars');
        $data['project_title'] = Request::post('project_title','','trim,strip_tags,htmlspecialchars');
        $data['category_id'] = $category_id;
        $data['merchant_id'] = self::$user_id;
        $data['price'] = fen_to_int(Request::post('price',0.00,'floatval'));
        $data['total'] = Request::post('total',0,'intval');
        $data['end_dt'] = Request::post('end_dt',NULL,'trim');
        $data['limited_submit'] = Request::post('limited_submit',0,'intval');
        $data['audit_cycle'] = Request::post('audit_cycle',0,'intval');

        //非必填
        $data['link'] = Request::post('link','','trim,strip_tags');
        $data['text_require'] = Request::post('text_require','','trim,strip_tags');
        //更新时间
        $data['refresh_time'] = time();
        $data['refresh_dt'] = date('Y-m-d H:i:s',$data['refresh_time']);

        $data['show_dt'] = NULL;
        $data['status'] = 1;//待支付

        //处理 steps
        $steps = Request::post('steps',[]);
        $stepList = [];
        foreach ($steps as $step){
            if(isset($step['type']) && in_array($step['type'],[1,2]) && isset($step['describe']) && isset($step['image'])){
                $stepList[] = [
                    'type'=>$step['type'],
                    'image'=>strip_tags(trim($step['image'])),
                    'describe'=>htmlspecialchars(strip_tags(trim($step['describe']))),
                ];
            }
        }

        $order = [];
        $order['order_no'] = get_rand_order_no();
        $order['user_id'] = self::$user_id;
        $order['product_type'] = 11;
        $order['product_title'] = '发布活动';
        $order['money'] = $data['total'] * $data['price'];
        $order['status'] = 1;
        $order['end_dt'] = date('Y-m-d H:i:s',time() + 1800);
        //父级
        $order['parent_id'] = self::$user['parent_id'];
        $order['parent2_id'] = self::$user['parent2_id'];


        Db::startTrans();
        try{

            $activity = ActivityModel::create($data);
            if(!$activity){
                throw new DbException('创建失败',50001);
            }

            //更新子项
            foreach ($stepList as $key=>$step){
                $stepList[$key]['activity_id'] = $activity['id'];
            }

            (new ActivityStepModel)->saveAll($stepList);

            //插入订单
            $order['product_id'] = $activity['id'];
            $order['product_spec_id'] = 0;
            $order = OrderModel::create($order);
            if(!$order){
                throw new DbException('创建失败:写入订单失败',50001);
            }

            Db::commit();
            return Responses::data(200, 'success',[
                'id'=>$activity['id'],
                'order'=>[
                    'id'=>$order['id'],
                    'money'=>fen_to_float($order['money'])
                ]
            ]);

        }catch (Exception $e){

            Db::rollback();
            return Responses::data(50002, '创建失败');

        }

    }


    /**
     * 活动更新
     *
     * @return Responses
     * */
    public function update()
    {

        $this->initAuthInfo();

        $datas = Request::post();

        //表单验证
        $validate = new ActivitySaveValidate();
        $vResult = $validate->scene(__FUNCTION__)->check($datas);
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $activity_id = Request::post('id',0,'intval');
        $activity_info = ActivityModel::where('id','=',$activity_id)
            ->where('merchant_id','=',self::$user_id)
            ->find();
        if(!$activity_info){
            throw new RequestException( '活动不存在',40003);
        }

        $data = [];
        $data['title'] = Request::post('title','','trim,strip_tags,htmlspecialchars');
        $data['project_title'] = Request::post('project_title','','trim,strip_tags,htmlspecialchars');
        $data['end_dt'] = Request::post('end_dt',NULL,'trim');
        $data['limited_submit'] = Request::post('limited_submit',0,'intval');
        $data['audit_cycle'] = Request::post('audit_cycle',0,'intval');

        //非必填
        $data['link'] = Request::post('link','','trim,strip_tags');
        $data['text_require'] = Request::post('text_require','','trim,strip_tags');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['status'] = 2;//待审核

        //处理 steps
        $steps = Request::post('steps',[]);
        $stepList = [];
        foreach ($steps as $step){
            if(isset($step['type']) && in_array($step['type'],[1,2]) && isset($step['describe']) && isset($step['image'])){
                $id = isset($step['id']) && $step['id'] > 0 ? (int)$step['id'] : 0;
                $item = [
                    'activity_id'=>$activity_id,
                    'type'=>$step['type'],
                    'image'=>strip_tags(trim($step['image'])),
                    'describe'=>htmlspecialchars(strip_tags(trim($step['describe']))),
                ];

                if($id){
                    $item['id'] = $id;
                }

                $stepList[] = $item;
            }
        }

        Db::startTrans();
        try{

            $result = ActivityModel::where('id','=',$activity_id)->update($data);
            if(!$result){
                throw new DbException('更新失败',50001);
            }

            //更新子项
            $step_ids = [];
            foreach ($stepList as $key=>$step){
                if(isset($step['id'])){
                    $id = $step['id'];
                    unset($step['id']);
                    $step['updated_at'] = date('Y-m-d H:i:s');
                    $step_result = ActivityStepModel::where('id','=',$id)
                        ->where('activity_id','=',$activity_id)
                        ->update($step);
                    if($step_result){
                        $step_ids[] = $id;
                    }
                }else{
                    $result = ActivityStepModel::create($step);
                    if($result){
                        $step_ids[] = $result['id'];
                    }
                }
            }

            //删除残余的子项
            ActivityStepModel::where('activity_id','=',$activity_id)
                ->whereNotIn('id',$step_ids)
                ->delete();

            Db::commit();
            return Responses::data(200, 'success',[
                'id'=>$activity_id,
            ]);

        }catch (Exception $e){

            Db::rollback();
            return Responses::data(50002, '创建失败');

        }

    }



    /**
     * 追加数量
     *
     * @return Responses
     * */
    public function addTo()
    {

        $this->initAuthInfo();

        //表单验证
        $validate = new ActivityAddToValidate();
        $vResult = $validate->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $activity_id = Request::post('id',0,'intval');
        $activity_info = ActivityModel::where('id','=',$activity_id)
            ->where('merchant_id','=',self::$user_id)
            ->find();
        if(!$activity_info){
            throw new RequestException( '活动不存在',40003);
        }

        //待支付
        if($activity_info['status'] == 1){
            throw new RequestException( '活动待支付',40003);
        }

        $data = [];
        $data['activity_id'] = $activity_id;
        $data['number'] = Request::post('number',0,'intval');
        $data['price'] = $activity_info['price'];
        $data['status'] = 1;

        $order = [];
        $order['order_no'] = get_rand_order_no();
        $order['user_id'] = self::$user_id;
        $order['product_type'] = 13;
        $order['product_title'] = '活动加量';
        $order['product_id'] = $activity_info['id'];
        $order['money'] = $data['number'] * $activity_info['price'];
        $order['status'] = 1;
        $order['end_dt'] = date('Y-m-d H:i:s',time() + 1800);

        //父级
        $order['parent_id'] = self::$user['parent_id'];
        $order['parent2_id'] = self::$user['parent2_id'];

        Db::startTrans();
        try{

            $result = ActivityAddToModel::create($data);
            if(!$result){
                throw new DbException('加量失败',50001);
            }

            //插入订单
            $order['product_spec_id'] = $result['id'];
            $order = OrderModel::create($order);
            if(!$order){
                throw new DbException('加量失败:写入订单失败',50001);
            }

            Db::commit();
            return Responses::data(200, 'success',[
                'id'=>$activity_id,
                'addto_id'=>$result['id'],
                'order'=>[
                    'id'=>$order['id'],
                    'money'=>fen_to_float($order['money'])
                ]
            ]);

        }catch (Exception $e){

            Db::rollback();
            return Responses::data(50002, '加量失败');

        }

    }



    /**
     * 活动刷新
     *
     * @return Responses
     * */
    public function refresh()
    {

        $this->initAuthInfo();

        //表单验证
        $validate = new ActivityValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $activity_id = Request::post('id',0,'intval');
        $activity_info = ActivityModel::where('id','=',$activity_id)
            ->where('merchant_id','=',self::$user_id)
            ->find();
        if(!$activity_info){
            throw new RequestException( '活动不存在',40003);
        }

        //判断刷新包
        if(self::$user['refresh_number'] < 1){
            throw new RequestException( '刷新道具数量不足，请先购买',40003);
        }

        //待支付
        if($activity_info['status'] == 1){
            throw new RequestException( '活动待支付',40003);
        }else if(in_array($activity_info['status'],[2,3])){
            throw new RequestException( '活动待审核',40003);
        }else if($activity_info['status'] == 4){
            throw new RequestException( '活动已结束',40003);
        }

        $data = [];
        $data['activity_id'] = $activity_id;

        $activity_data = [];
        $activity_data['refresh_time'] = time();
        $activity_data['refresh_dt'] = date('Y-m-d H:i:s',$activity_data['refresh_time']);
        $activity_data['updated_at'] = date('Y-m-d H:i:s');

        Db::startTrans();
        try{

            //更新刷新道具数量
            UserModel::where('id','=',self::$user_id)->setDec('refresh_number',1);

            $result = ActivityRefreshModel::create($data);
            if(!$result){
                throw new DbException('刷新失败',50001);
            }

            //更新活动
            ActivityModel::where('id','=',$activity_id)
                ->update($activity_data);

            Db::commit();
            return Responses::data(200, 'success',[
                'id'=>$activity_id,
                'refresh_id'=>$result['id']
            ]);

        }catch (Exception $e){

            Db::rollback();
            return Responses::data(50002, '刷新失败');

        }

    }



    /**
     * 活动推荐
     *
     * @return Responses
     * */
    public function recommend()
    {

        $this->initAuthInfo();

        //表单验证
        $validate = new ActivityValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $activity_id = Request::post('id',0,'intval');
        $activity_info = ActivityModel::where('id','=',$activity_id)
            ->where('merchant_id','=',self::$user_id)
            ->find();
        if(!$activity_info){
            throw new RequestException( '活动不存在',40003);
        }

        //待支付
        if($activity_info['status'] == 1){
            throw new RequestException( '活动待支付',40003);
        }else if(in_array($activity_info['status'],[2,3])){
            throw new RequestException( '活动待审核',40003);
        }else if($activity_info['status'] == 4){
            throw new RequestException( '活动已结束',40003);
        }

        //推荐信息
        $data = [];
        $data['activity_id'] = $activity_id;
        $data['hour'] = Request::post('hour',0,'intval');
        $data['price'] = dbConfig('activity.rec_money',0);//10元
        $data['total_price'] = $data['hour'] * $data['price'];
        $data['discount_amount'] = 0;
        $data['discount_total_price'] = 0;

        //超级商家
        if(self::$user['merchant_level'] == 1){
            $rec_sc_rate = dbConfig('activity.rec_merchant_sc_rate',100);
        }else if(self::$user['user_level'] == 1){
            $rec_sc_rate = dbConfig('activity.rec_user_sc_rate',100);
        }else{
            $rec_sc_rate = dbConfig('activity.rec_sc_rate',100);
        }

        $rec_sc_rate = fen_to_float($rec_sc_rate);

        $data['discount_amount'] = $data['price'] / $rec_sc_rate;
        $data['discount_total_price'] = $data['discount_amount'] * $data['hour'];

        $data['pay_amount'] = $data['total_price'] - $data['discount_total_price'];
        $data['vip_level'] = self::$user['merchant_level'] == 1 || self::$user['user_level'] == 1 ? 1 : 0;
        $data['status'] = 1;

        //订单信息
        $order = [];
        $order['order_no'] = get_rand_order_no();
        $order['user_id'] = self::$user_id;
        $order['product_type'] = 14;
        $order['product_title'] = '推荐活动';
        $order['product_id'] = $activity_info['id'];
        $order['money'] = $data['pay_amount'];
        $order['status'] = 1;
        $order['end_dt'] = date('Y-m-d H:i:s',time() + 1800);

        //父级
        $order['parent_id'] = self::$user['parent_id'];
        $order['parent2_id'] = self::$user['parent2_id'];

        Db::startTrans();
        try{

            $result = ActivityRecModel::create($data);
            if(!$result){
                throw new DbException('推荐失败',50001);
            }

            //插入订单
            $order['product_spec_id'] = $result['id'];
            $order = OrderModel::create($order);
            if(!$order){
                throw new DbException('推荐失败:写入订单失败',50001);
            }

            Db::commit();
            return Responses::data(200, 'success',[
                'id'=>$activity_id,
                'rec_id'=>$result['id'],
                'order'=>[
                    'id'=>$order['id'],
                    'money'=>fen_to_float($order['money'])
                ]
            ]);

        }catch (Exception $e){

            Db::rollback();
            return Responses::data(50002, '推荐失败');

        }

    }


    /**
     * 审核列表
     *
     * @return Responses
     * */
    public function auditList()
    {

        $this->initAuthInfo();

        $activity_id = Request::post('filters.activity_id',0,'intval');

        //表单验证
        $validate = new ActivityLogValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(['activity_id'=>$activity_id]);
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }


        $ActivityAuditModel = new ActivityAuditModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $ActivityAuditModel = $ActivityAuditModel->where('id','in',$value);
                        }
                        break;
                    case 'activity_id':
                        $value = (int)$value;
                        if($value){
                            $ActivityAuditModel = $ActivityAuditModel->where('activity_id','=',$value);
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $ActivityAuditModel = $ActivityAuditModel->where('status','in',$value);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));
        $lists = $ActivityAuditModel
            ->order('id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = $lists['data'];

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


    /**
     * 刷新列表
     *
     * @return Responses
     * */
    public function refreshList()
    {

        $this->initAuthInfo();

        $activity_id = Request::post('filters.activity_id',0,'intval');

        //表单验证
        $validate = new ActivityLogValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(['activity_id'=>$activity_id]);
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }


        $ActivityRefreshModel = new ActivityRefreshModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $ActivityRefreshModel = $ActivityRefreshModel->where('id','in',$value);
                        }
                        break;
                    case 'activity_id':
                        $value = (int)$value;
                        if($value){
                            $ActivityRefreshModel = $ActivityRefreshModel->where('activity_id','=',$value);
                        }
                        break;
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));
        $lists = $ActivityRefreshModel
            ->order('id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = $lists['data'];

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


    /**
     * 增量列表
     *
     * @return Responses
     * */
    public function addtoList()
    {

        $this->initAuthInfo();

        $activity_id = Request::post('filters.activity_id',0,'intval');

        //表单验证
        $validate = new ActivityLogValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(['activity_id'=>$activity_id]);
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }


        $ActivityAddToModel = new ActivityAddToModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $ActivityAddToModel = $ActivityAddToModel->where('id','in',$value);
                        }
                        break;
                    case 'activity_id':
                        $value = (int)$value;
                        if($value){
                            $ActivityAddToModel = $ActivityAddToModel->where('activity_id','=',$value);
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $ActivityAddToModel = $ActivityAddToModel->where('status','in',$value);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));
        $lists = $ActivityAddToModel
            ->order('id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = [];
        if($lists['data']){
            foreach ($lists['data'] as $data){
                $data['price'] = fen_to_float($data['price']);
                $datas[] = $data;
            }
        }

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }


    /**
     * 上推荐列表
     *
     * @return Responses
     * */
    public function recommendList()
    {

        $this->initAuthInfo();

        $activity_id = Request::post('filters.activity_id',0,'intval');

        //表单验证
        $validate = new ActivityLogValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(['activity_id'=>$activity_id]);
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }


        $ActivityRecModel = new ActivityRecModel();

        //处理过滤
        $filters = Request::post('filters',[]);
        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $ActivityRecModel = $ActivityRecModel->where('id','in',$value);
                        }
                        break;
                    case 'activity_id':
                        $value = (int)$value;
                        if($value){
                            $ActivityRecModel = $ActivityRecModel->where('activity_id','=',$value);
                        }
                        break;
                    case 'status':
                        if($value && is_array($value)){
                            $ActivityRecModel = $ActivityRecModel->where('status','in',$value);
                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));
        $lists = $ActivityRecModel
            ->order('id DESC')
            ->paginate($page_size)
            ->toArray();

        $datas = [];
        if($lists['data']){
            foreach ($lists['data'] as $data){
                $data['pay_amount'] = fen_to_float($data['pay_amount']);
                $datas[] = $data;
            }
        }

        //组装分页
        $pages = get_list_pages($lists);
        return Responses::data(200, 'success',$datas,compact('pages'));

    }



}
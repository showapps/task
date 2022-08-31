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
use app\common\model\ActivityCategoryModel;
use app\common\model\ActivityModel;
use app\admin\validate\ActivityValidate;
use app\common\model\MessageModel;
use think\facade\Request;
use think\facade\View;
use Tools\Responses;

class ActivityController
{

    use AuthTrait;

    protected $directory = 'activity';

    public function __construct()
    {
        $this->initAuthInfo();
    }





    public function index()
    {
        $categorys = ActivityCategoryModel::field('id,title')->all();
        return view($this->directory . '/index',compact('categorys'));
    }




    /**
     * 数据列表
     *
     * @return Responses
     * */
    public function lists()
    {

        $ActivityModel = new ActivityModel();

        //处理过滤
        $filters = Request::post('filters',[]);

        if($filters && is_array($filters)){
            foreach ($filters as $name=>$value){
                switch ($name){
                    case 'ids':
                        if($value && is_array($value)){
                            $ActivityModel = $ActivityModel->where('id','in',$value);
                        }
                        break;
                    case 'category_id':
                        $value = intval($value);
                        if($value){
                            $ActivityModel = $ActivityModel->where('category_id','=',$value);
                        }
                        break;
                    case 'search_text':
                        $value = htmlspecialchars(strip_tags(trim($value)));
                        if($value){

                            if(is_numeric($value)){
                                $value = intval($value);
                                $ActivityModel = $ActivityModel->where('(id='.$value.' OR merchant_id='.$value.' OR title LIKE "'.$value.'%")');
                            }else{
                                $ActivityModel = $ActivityModel->where('title','like',$value.'%');
                            }

                        }
                        break;
                }
            }
        }

        $page_size = Request::post('page_size',env('page_size',10));

        $lists = $ActivityModel
            ->with([
                'category'=>function($query){
                    $query->field('id,title');
                },
                'merchant'=>function($query){
                    $query->field('id,nick_name');
                }
            ])
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
     * 数据详情
     *
     * @return Responses
     * */
    public function detail()
    {

        //表单验证
        $validate = new ActivityValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::param());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::param('id');
        $info = ActivityModel::where('id','=',$id)->with([
            'category'=>function($query){
                $query->field('id,title');
            },
            'merchant'=>function($query){
                $query->field('id,nick_name');
            },
            'steps'=>function($query){
                $query->field('activity_id,type,image,describe');
            }
        ])->find();
        if(!$info){
            throw new RequestException( '活动不存在',40401);
        }

        $info['price'] = fen_to_float($info['price']);

        $data = (string)View::fetch($this->directory . '/detail',compact('info'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 审核模板
     *
     * @return Responses
     * */
    public function audit_view()
    {

        //表单验证
        $validate = new ActivityValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = ActivityModel::where('id','=',$id)->with([
            'category'=>function($query){
                $query->field('id,title');
            },
            'merchant'=>function($query){
                $query->field('id,nick_name');
            },
            'steps'=>function($query){
                $query->field('activity_id,type,image,describe');
            }
        ])->find();
        if(!$info){
            throw new RequestException( '活动不存在',40401 );
        }

        if($info['status'] == 1){
            throw new RequestException( '活动待支付',40401 );
        }

        if($info['status'] != 2){
            throw new RequestException( '活动已审核',40401 );
        }

        $info['price'] = fen_to_float($info['price']);

        $data = (string)View::fetch($this->directory . '/audit',compact('info'));
        return Responses::data(200, 'success',$data);

    }


    /**
     * 审核活动
     *
     * @return Responses
     * */
    public function audit()
    {

        //表单验证
        $validate = new ActivityValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $id = Request::post('id');
        $info = ActivityModel::where('id','=',$id)->find();
        if(!$info){
            throw new RequestException( '活动不存在',40401 );
        }

        if($info['status'] == 1){
            throw new RequestException( '活动待支付',40401 );
        }

        if($info['status'] != 2){
            throw new RequestException( '活动已审核',40401 );
        }

        $data = [];
        $data['status'] = Request::post('status',3,'intval') == 100 ? 100 : 3;
        $data['show_dt'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        //驳回理由
        $reasons = Request::post('reasons','','trim,strip_tags,htmlspecialchars');

        try {

            $result = ActivityModel::where('id','=',$id)
                ->where('status','=',2)
                ->update($data);

            if(!$result){
                throw new DbException('审核失败',50001);
            }

            $messageContent = '';
            if($data['status'] == 100){
                $messageContent = '恭喜您，您的任务'.$info['title'].'已经审核完毕，请关注接单信息';
            }else{
                $messageContent = '很抱歉，您的任务'.$info['title'].'已驳回,驳回理由：'.$reasons.'，请修改任务重新提交';
            }

            MessageModel::create([
                'user_id'=>$info['user_id'],
                'category'=>2001,
                'content'=>$messageContent,
                'link'=>[],
                'status'=>2,
            ]);

            return Responses::data(200, 'success');

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }


}
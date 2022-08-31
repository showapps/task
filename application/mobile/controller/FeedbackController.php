<?php
/**
 * FeedbackController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/9/29
 */

namespace app\mobile\controller;


use app\common\exception\DbException;
use app\common\exception\RequestException;
use app\common\model\FeedbackModel;
use app\mobile\traits\AuthTrait;
use app\mobile\validate\FeedbackValidate;
use think\facade\Request;
use Tools\Auth;
use Tools\Responses;


class FeedbackController
{

    use AuthTrait;




    /**
     * 添加反馈
     *
     * @return Responses
     * */
    public function create()
    {

        $this->initAuthInfo();

        //表单验证
        $validate = new FeedbackValidate();
        $vResult = $validate->scene(__FUNCTION__)->check(Request::post());
        if(!$vResult){
            throw new RequestException( $validate->getError(),40003);
        }

        $data['user_id'] = self::$user_id;
        $data['content'] = Request::post('content','','trim');
        $data['images'] = Request::post('images',[]);
        $data['status'] = 1;

        try {

            $feedback = FeedbackModel::create($data);
            if(!$feedback){
                throw new DbException('反馈失败',50001);
            }
            return Responses::data(200, 'success',['id'=>$feedback['id']]);

        }catch (DbException $e){
            return Responses::data(50001, $e->getMessage());
        }

    }

}
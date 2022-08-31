<?php
/**
 * UserRegisterBehavior.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/6
 */

namespace app\common\behavior;


use app\common\model\MessageModel;
use app\common\model\UserExtModel;
use app\common\model\UserModel;
use think\facade\Request;

class UserRegisterBehavior
{
    public function run(Request $request, $user)
    {

        //创建扩展
        $user_ext = UserExtModel::create([
            'user_id'=> $user['id']
        ]);

        //更新邀请码
        UserModel::where('id','=',$user['id'])->update([
            'invitation_code'=>get_invitation_code($user['id'])
        ]);

        //更新上级的下线数量
        if($user['parent_id'] >= 1){
            UserModel::where('id','=',$user['parent_id'])->setInc('child_total',1);
        }

        //更新上上级的二级下线数量
        if($user['parent2_id'] >= 1){
            UserModel::where('id','=',$user['parent2_id'])->setInc('child2_total',1);
        }

        //更新上级消息
        if($user['parent_id'] >= 1){
            MessageModel::create([
                'user_id'=>$user['parent_id'],
                'category'=>2002,
                'content'=>'恭喜您，您推荐的会员'.$user['nick_name'].'已注册成功，请及时联系！',
                'link'=>[],
                'status'=>2,
            ]);
        }

    }
}
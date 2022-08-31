<?php
/**
 * WechatController.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/10/22
 */

namespace app\mobile\controller;


use Tools\Responses;

class WechatController
{

    public function share(){

        $data = [];
        $data['title'] = 'A随风喊你一起来赚钱啦！';
        $data['description'] = '我正在人人任务做任务赚钱，邀请你也来参加，提现及时到账，靠谱！';
        $data['image'] = 'https://task.kechuang.link/static/logo.png';
        $data['link'] = 'https://m.task.kechuang.link/#/pages/index/index?share_code=X3YS75';
        return Responses::data(200,'success',$data);

    }

}
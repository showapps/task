<?php

namespace app\admin\controller;


use Gregwar\Captcha\PhraseBuilder;
use think\facade\Session;

class CaptchaController
{



    /**
     * 图片验证码
     * */
    public function image()
    {

        $phraseBuilder = new PhraseBuilder(4, '123456789');
        $builder = new \Gregwar\Captcha\CaptchaBuilder(null,$phraseBuilder);
        $builder->setDistortion(true);
        $builder->setBackgroundColor(245, 247, 250);
        $captcha = $builder->build();
        $captcha_content = $captcha->getPhrase();

        Session::set('admin_captcha_image',$captcha_content);

        //生成图片
        header("Cache-Control: no-cache, must-revalidate");
        header('Content-Type: image/jpeg');
        $builder->output();

    }

}
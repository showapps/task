<?php


namespace app\admin\controller;


use app\admin\traits\AuthTrait;
use think\Exception;
use think\facade\Session;
use Tools\AdminPermission;
use Tools\Responses;

class SystemController
{

    use AuthTrait;

    /**
     * 数据详情
     *
     * @return Responses
     * */
    public function detail()
    {

        $this->initAuthInfo();

        $datas = [];

        //首页
        $datas['homeInfo'] = [
            'title'=>"首页",
            'href'=>tab_admin_url('welcome',['_v'=>1]),
        ];

        //LOGO
        $datas['logoInfo'] = [
            'title'=>"管理平台",
            'image'=>'/static/logo.png?v=1',
            'href'=>admin_url('index'),
        ];

        //菜单
        $menuJson = '
        
[{"title":"基础","icon":"fa fa-address-book","href":"","target":"_self","child":[{"title":"用户管理","href":"","icon":"fa fa-users","target":"_self","child":[{"title":"用户列表","href":"/admin/user/list.shtml","icon":"fa fa-circle-o","refresh":false,"target":"_self"},{"title":"黑名单列表","href":"/admin/blacklist/list.shtml","icon":"fa fa-circle-o","refresh":false,"target":"_self"}]},{"title":"活动管理","href":"","icon":"fa fa-list","target":"_self","child":[{"title":"活动分类","href":"/admin/activity_category/list.shtml","icon":"fa fa-circle-o","refresh":false,"target":"_self"},{"title":"活动列表","href":"/admin/activity/list.shtml","icon":"fa fa-circle-o","refresh":false,"target":"_self"}]},{"title":"任务管理","href":"","icon":"fa fa-list-ol","target":"_self","child":[{"title":"任务列表","href":"/admin/task/list.shtml","icon":"fa fa-circle-o","refresh":false,"target":"_self"}]},{"title":"财务管理","href":"","icon":"fa fa-credit-card","target":"_self","child":[{"title":"提现列表","href":"/admin/wallet/withdraw/list.shtml","icon":"fa fa-circle-o","refresh":false,"target":"_self"}]}]},{"title":"系统","icon":"fa fa-lemon-o","href":"","target":"_self","child":[{"title":"系统设置","href":"","icon":"fa fa-cogs","target":"_self","child":[{"title":"基础信息","href":"/admin/setting/basic.shtml","icon":"fa fa-circle-o","refresh":true,"target":"_self"},{"title":"支付设置","href":"/admin/setting/payment.shtml","icon":"fa fa-circle-o","refresh":true,"target":"_self"},{"title":"上传设置","href":"/admin/setting/upload.shtml","icon":"fa fa-circle-o","refresh":true,"target":"_self"},{"title":"用户设置","href":"/admin/setting/vip.shtml","icon":"fa fa-circle-o","refresh":true,"target":"_self"},{"title":"提现设置","href":"/admin/setting/withdraw.shtml","icon":"fa fa-circle-o","refresh":true,"target":"_self"}]},{"title":"平台管理","href":"","icon":"fa fa-desktop","target":"_self","child":[{"title":"账户列表","href":"/admin/admin/list.shtml","icon":"fa fa-circle-o","refresh":false,"target":"_self"},{"title":"角色列表","href":"/admin/admin/role/list.shtml","icon":"fa fa-circle-o","refresh":false,"target":"_self"}]},{"title":"广告管理","href":"","icon":"fa fa-photo","target":"_self","child":[{"title":"广告列表","href":"/admin/a/list.shtml","icon":"fa fa-circle-o","refresh":false,"target":"_self"}]},{"title":"帮助管理","href":"","icon":"fa fa-newspaper-o","target":"_self","child":[{"title":"帮助分类","href":"/admin/help_category/list.shtml","icon":"fa fa-circle-o","refresh":false,"target":"_self"},{"title":"帮助列表","href":"/admin/help/list.shtml","icon":"fa fa-circle-o","refresh":false,"target":"_self"}]},{"title":"单页管理","href":"","icon":"fa fa-code","target":"_self","child":[{"title":"单页列表","href":"/admin/page/list.shtml","icon":"fa fa-circle-o","refresh":false,"target":"_self"}]}]}]
        
        ';
        //$datas['menuInfo'] = json_decode($menuJson,true);

        $role_id = Session::get('role_id');

        $adminPermission = new AdminPermission();
        $adminPermission->setRoleId($role_id);

        $datas['menuInfo'] = [];

        $base = [
            'title'=>'基础',
            'icon'=>'fa fa-address-book',
            'href'=>'',
            'target'=>'_self',
            'child'=>[],
        ];


        //用户管理
        $members = [
            'title'=>'用户管理','icon'=>'fa fa-users','href'=>'','target'=>'_self','child'=>[]
        ];

        $adminPermission->setModuleName('user');
        //检查操作权限
        $adminPermission->setActionName('read');
        if($adminPermission->checkActionAccess()){
            $members['child'][] = [
                'title'=>'用户列表','href'=>'/admin/user/list.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        $adminPermission->setModuleName('blacklist');
        //检查操作权限
        $adminPermission->setActionName('read');
        if($adminPermission->checkActionAccess()){
            $members['child'][] = [
                'title'=>'黑名单列表','href'=>'/admin/blacklist/list.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        if(count($members['child'])){
            $base['child'][] = $members;
        }

        //反馈管理
        $feedbacks = [
            'title'=>'反馈管理','icon'=>'fa fa-list','href'=>'','target'=>'_self','child'=>[]
        ];

        $adminPermission->setModuleName('feedback');
        //检查操作权限
        $adminPermission->setActionName('read');
        if($adminPermission->checkActionAccess()){
            $feedbacks['child'][] = [
                'title'=>'反馈列表','href'=>'/admin/feedback/list.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        if(count($feedbacks['child'])){
            $base['child'][] = $feedbacks;
        }

        //排行榜管理
        $rankings = [
            'title'=>'排行榜管理','icon'=>'fa fa-list','href'=>'','target'=>'_self','child'=>[]
        ];

        $adminPermission->setModuleName('rankinglist');
        //检查操作权限
        $adminPermission->setActionName('read');
        if($adminPermission->checkActionAccess()){
            $rankings['child'][] = [
                'title'=>'排行榜列表','href'=>'/admin/rankinglist/list.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        if(count($rankings['child'])){
            $base['child'][] = $rankings;
        }

        //活动管理
        $activitys = [
            'title'=>'活动管理','icon'=>'fa fa-list','href'=>'','target'=>'_self','child'=>[]
        ];

        $adminPermission->setModuleName('activity_category');
        //检查操作权限
        $adminPermission->setActionName('read');
        if($adminPermission->checkActionAccess()){
            $activitys['child'][] = [
                'title'=>'活动分类','href'=>'/admin/activity_category/list.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        $adminPermission->setModuleName('activity');
        //检查操作权限
        $adminPermission->setActionName('read');
        if($adminPermission->checkActionAccess()){
            $activitys['child'][] = [
                'title'=>'活动列表','href'=>'/admin/activity/list.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        if(count($activitys['child'])){
            $base['child'][] = $activitys;
        }

        //任务管理
        $tasks = [
            'title'=>'任务管理','icon'=>'fa fa-list-ol','href'=>'','target'=>'_self','child'=>[]
        ];

        $adminPermission->setModuleName('task');
        //检查操作权限
        $adminPermission->setActionName('read');
        if($adminPermission->checkActionAccess()){
            $tasks['child'][] = [
                'title'=>'任务列表','href'=>'/admin/task/list.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        if(count($tasks['child'])){
            $base['child'][] = $tasks;
        }

        //财务管理
        $finances = [
            'title'=>'财务管理','icon'=>'fa fa-credit-card','href'=>'','target'=>'_self','child'=>[]
        ];

        $adminPermission->setModuleName('finance');
        //检查操作权限
        $adminPermission->setActionName('read');
        if($adminPermission->checkActionAccess()){
            $finances['child'][] = [
                'title'=>'提现列表','href'=>'/admin/wallet/withdraw/list.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        if(count($finances['child'])){
            $base['child'][] = $finances;
        }

        //基础菜单
        if(count($base['child'])){
            $datas['menuInfo'][] = $base;
        }


        $system = [
            'title'=>'系统',
            'icon'=>'fa fa-lemon-o',
            'href'=>'',
            'target'=>'_self',
            'child'=>[],
        ];

        $settings = [
            'title'=>'系统设置',
            'icon'=>'fa fa-cogs',
            'href'=>'',
            'target'=>'_self',
            'child'=>[]
        ];

        //系统
        $adminPermission->setModuleName('setting');
        //检查操作权限
        $adminPermission->setActionName('basic');
        if($adminPermission->checkActionAccess()){
            $settings['child'][] = [
                'title'=>'基础信息','href'=>'/admin/setting/basic.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        //检查操作权限
        $adminPermission->setActionName('payment');
        if($adminPermission->checkActionAccess()){
            $settings['child'][] = [
                'title'=>'支付设置','href'=>'/admin/setting/payment.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        //检查操作权限
        $adminPermission->setActionName('sms');
        if($adminPermission->checkActionAccess()){
            $settings['child'][] = [
                'title'=>'短信设置','href'=>'/admin/setting/sms.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        //检查操作权限
        $adminPermission->setActionName('upload');
        if($adminPermission->checkActionAccess()){
            $settings['child'][] = [
                'title'=>'上传设置','href'=>'/admin/setting/upload.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        //检查操作权限
        $adminPermission->setActionName('vip');
        if($adminPermission->checkActionAccess()){
            $settings['child'][] = [
                'title'=>'用户设置','href'=>'/admin/setting/vip.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        //检查操作权限
        $adminPermission->setActionName('withdraw');
        if($adminPermission->checkActionAccess()){
            $settings['child'][] = [
                'title'=>'提现设置','href'=>'/admin/setting/withdraw.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        //检查操作权限
        $adminPermission->setActionName('ranking');
        if($adminPermission->checkActionAccess()){
            $settings['child'][] = [
                'title'=>'排行榜设置','href'=>'/admin/setting/ranking.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        //检查操作权限
        $adminPermission->setActionName('commission');
        if($adminPermission->checkActionAccess()){
            $settings['child'][] = [
                'title'=>'分佣设置','href'=>'/admin/setting/commission.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        //检查操作权限
        $adminPermission->setActionName('money');
        if($adminPermission->checkActionAccess()){
            $settings['child'][] = [
                'title'=>'金额设置','href'=>'/admin/setting/money.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        //检查操作权限
        $adminPermission->setActionName('novice_reward');
        if($adminPermission->checkActionAccess()){
            $settings['child'][] = [
                'title'=>'新手奖励','href'=>'/admin/setting/novice_reward.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        //检查操作权限
        $adminPermission->setActionName('friend_award');
        if($adminPermission->checkActionAccess()){
            $settings['child'][] = [
                'title'=>'好友奖励','href'=>'/admin/setting/friend_award.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        //检查操作权限
        $adminPermission->setActionName('refresh_number');
        if($adminPermission->checkActionAccess()){
            $settings['child'][] = [
                'title'=>'刷新配置','href'=>'/admin/setting/refresh_number.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        if(count($settings['child'])){
            $system['child'][] = $settings;
        }

        //平台
        $platforms = [
            'title'=>'平台管理','icon'=>'fa fa-desktop','href'=>'','target'=>'_self','child'=>[]
        ];

        $adminPermission->setModuleName('admin');
        //检查操作权限
        $adminPermission->setActionName('read');
        if($adminPermission->checkActionAccess()){
            $platforms['child'][] = [
                'title'=>'账户列表','href'=>'/admin/admin/list.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }
        $adminPermission->setModuleName('admin_role');
        //检查操作权限
        $adminPermission->setActionName('read');
        if($adminPermission->checkActionAccess()){
            $platforms['child'][] = [
                'title'=>'角色列表','href'=>'/admin/admin/role/list.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        if(count($platforms['child'])){
            $system['child'][] = $platforms;
        }

        //广告
        $ads = [
            'title'=>'广告管理','icon'=>'fa fa-photo','href'=>'','target'=>'_self','child'=>[]
        ];

        $adminPermission->setModuleName('ad');
        //检查操作权限
        $adminPermission->setActionName('read');
        if($adminPermission->checkActionAccess()){
            $ads['child'][] = [
                'title'=>'广告列表','href'=>'/admin/a/list.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        if(count($ads['child'])){
            $system['child'][] = $ads;
        }

        //帮助管理
        $helps = [
            'title'=>'帮助管理','icon'=>'fa fa-newspaper-o','href'=>'','target'=>'_self','child'=>[]
        ];

        $adminPermission->setModuleName('help_category');
        //检查操作权限
        $adminPermission->setActionName('read');
        if($adminPermission->checkActionAccess()){
            $helps['child'][] = [
                'title'=>'帮助分类','href'=>'/admin/help_category/list.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        $adminPermission->setModuleName('help');
        //检查操作权限
        $adminPermission->setActionName('read');
        if($adminPermission->checkActionAccess()){
            $helps['child'][] = [
                'title'=>'帮助列表','href'=>'/admin/help/list.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        if(count($helps['child'])){
            $system['child'][] = $helps;
        }

        //单页
        $pages = [
            'title'=>'单页管理','icon'=>'fa fa-code','href'=>'','target'=>'_self','child'=>[]
        ];

        $adminPermission->setModuleName('page');
        //检查操作权限
        $adminPermission->setActionName('read');
        if($adminPermission->checkActionAccess()){
            $pages['child'][] = [
                'title'=>'单页列表','href'=>'/admin/page/list.shtml','icon'=>'fa fa-circle-o',
                'refresh'=>true,'target'=>'_self',
            ];
        }

        if(count($pages['child'])){
            $system['child'][] = $pages;
        }

        if(count($system['child'])){
            $datas['menuInfo'][] = $system;
        }

        return Responses::data(200, 'success',$datas);

    }

}
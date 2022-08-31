<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

Route::get('api/mobile/auth/wechat','mobile/WechatBinding/binding')->ext('html');
Route::get('api/mobile/wechat/callback','mobile/WechatBinding/callback')->ext('html');


Route::group('api', function () {

    //控制台
    Route::group('console', function () {
        Route::post('user/level/end','console/User/end_user_level');
        Route::post('merchant/level/end','console/User/end_merchant_level');
        Route::post('blacklist/end','console/Blacklist/end');
        Route::post('ranking/task','console/Ranking/task');
        Route::post('ranking/spread','console/Ranking/spread');
        //任务
        Route::group('task', function () {
            Route::post('submit_end','console/Task/submit_end');
            Route::post('audit_end','mobile/Task/audit_end');
            Route::post('recheck_submit_end','console/Task/recheck_submit_end');
            Route::post('recheck_audit_end','console/Task/recheck_audit_end');
            Route::post('report_open_end','console/Task/report_open_end');
            Route::post('report_bl_end','console/Task/report_bl_end');
        });
    });

    Route::group('install', function () {

        //安装
        Route::post('create','install/Index/create');

    });

    Route::group('mobile', function () {

        Route::post('config/install','mobile/Config/install');
        Route::post('menu/install','mobile/Menu/install');
        //系统
        Route::group('system', function () {
            Route::post('config','mobile/System/config');
            Route::post('detail','mobile/System/detail');
        });

        Route::post('login','mobile/Login/phone');
        Route::post('logout','mobile/Auth/logout');
        Route::post('notify/wechat/payment','mobile/Notify/wechatPayment');

        //单页
        Route::group('page', function () {
            Route::post('list','mobile/Page/lists');
            Route::post('detail','mobile/Page/detail');
        });

        //验证码
        Route::group('captcha', function () {
            Route::post('image','mobile/Captcha/image');
            Route::post('sms','mobile/Captcha/sms');
        });

        //广告
        Route::group('gg', function () {
            Route::post('list','mobile/Ad/lists');
            Route::post('detail','mobile/Ad/detail');
        });

        //文件上传
        Route::group('upload', function () {
            Route::post('image/base64','service/Upload/image_base64');
            Route::post('images','service/Upload/images');
            Route::post('videos','service/Upload/videos');
            Route::post('voices','service/Upload/voices');
        });

        //文章
        Route::group('help', function () {
            Route::post('category','mobile/Help/categorys');
            Route::post('list','mobile/Help/lists');
            Route::post('detail','mobile/Help/detail');
        });

        //用户
        Route::group('user', function () {
            Route::post('list','mobile/User/lists');
            Route::post('detail','mobile/User/detail');
        });

        //活动
        Route::group('activity', function () {
            Route::post('category','mobile/Activity/categorys');
            Route::post('list','mobile/Activity/lists');
            Route::post('detail','mobile/Activity/detail');
        });

        //新手奖励
        Route::group('novice_reward', function () {
            Route::post('list','mobile/NoviceReward/lists');
            Route::post('detail','mobile/NoviceReward/detail');
        });

        //黑名单
        Route::group('blacklist', function () {
            Route::post('list','mobile/Blacklist/lists');
            Route::post('detail','mobile/Blacklist/detail');
        });

        //排名
        Route::group('ranking', function () {
            Route::post('list','mobile/Ranking/lists');
        });

        //分享
        Route::group('spread', function () {
            Route::post('notice','mobile/Spread/notice');
            Route::post('total','mobile/Spread/total')->middleware(\app\mobile\middleware\AuthMiddleware::class);
            Route::post('poster','mobile/Spread/poster')->middleware(\app\mobile\middleware\AuthMiddleware::class);
        });

        Route::group('wechat', function () {
            Route::group('mp', function () {
                Route::post('binding','mobile/Wechat/binding');
                Route::post('config','mobile/MpWechat/config');
                Route::post('share','mobile/Wechat/share');
            });
            Route::group('miniapp', function () {
                Route::post('binding','mobile/MiniApp/binding');
                Route::post('decode','mobile/MiniApp/decode');
            });
        });

        //需要授权
        Route::group('', function () {

            Route::post('user/fans','mobile/User/fansList');

            //活动
            Route::group('activity', function () {
                Route::post('create','mobile/Activity/create');
                Route::post('update','mobile/Activity/update');
                Route::post('addTo','mobile/Activity/addTo');
                Route::post('stop','mobile/Activity/stop');
                Route::post('refresh','mobile/Activity/refresh');
                Route::post('recommend','mobile/Activity/recommend');

                Route::group('', function () {
                    Route::post('merchant/total','mobile/Activity/merchant_total');
                    Route::post('auditList','mobile/Activity/auditList');
                    Route::post('refreshList','mobile/Activity/refreshList');
                    Route::post('addtoList','mobile/Activity/addtoList');
                    Route::post('recommendList','mobile/Activity/recommendList');
                })->middleware(\app\mobile\middleware\AuthMiddleware::class);
            });

            //反馈
            Route::group('feedback', function () {
                Route::post('create','mobile/Feedback/create');
            });

            //任务
            Route::group('task', function () {
                Route::post('list','mobile/Task/lists');
                Route::post('report/list','mobile/Task/reportList');
                Route::post('report/total','mobile/Task/reportTotal');
                Route::post('merchant/total','mobile/Task/merchant_total');
                Route::post('user/total','mobile/Task/user_total');
                Route::post('detail','mobile/Task/detail');
                Route::post('apply','mobile/Task/apply');
                Route::post('cancel','mobile/Task/cancel');
                Route::post('submit','mobile/Task/submit');
                Route::post('reject','mobile/Task/reject');
                Route::post('pass','mobile/Task/pass');
                Route::post('pass_list','mobile/Task/pass_list');
                Route::post('recheck/submit','mobile/Task/recheck_submit');
                Route::post('recheck/reject','mobile/Task/recheck_reject');
                Route::post('report/create','mobile/Task/report_create');
                Route::post('report/argue','mobile/Task/report_argue');
                Route::post('delete','mobile/Task/delete');
            });

            //订单
            Route::group('order', function () {
                Route::post('payment','mobile/Order/payment');
            });

            //佣金
            Route::group('commission', function () {
                Route::post('list','mobile/Commission/lists');
                Route::post('detail','mobile/Commission/detail');
            });

            //钱包
            Route::group('wallet', function () {
                Route::post('list','mobile/Wallet/lists');
                Route::post('detail','mobile/Wallet/detail');
                Route::post('payment','mobile/Wallet/payment');
                Route::post('recharge','mobile/Wallet/recharge');
                Route::post('withdraw','mobile/Wallet/withdraw');
                Route::post('account/list','mobile/Wallet/accountList');
                Route::post('account/detail','mobile/Wallet/accountDetail');
            });

            //押金
            Route::group('deposit', function () {
                Route::post('recharge','mobile/Deposit/recharge');
                Route::post('withdraw','mobile/Deposit/withdraw');
            });

            //消息
            Route::group('message', function () {
                Route::post('list','mobile/Message/lists');
                Route::post('detail','mobile/Message/detail');
            });

            //聊天
            Route::group('chat', function () {
                Route::post('list','mobile/Chat/lists');
                Route::post('content/list','mobile/Chat/contentList');
                Route::post('detail','mobile/Chat/detail');
                Route::post('open','mobile/Chat/open');
                Route::post('send','mobile/Chat/send');
            });

            //我的
            Route::group('me', function () {
                Route::post('detail','mobile/Me/detail');
                Route::post('update/:field','mobile/Me/updateField');
                Route::post('update','mobile/Me/update');
                Route::post('buyVip','mobile/Me/buyVip');
                Route::post('vipSku','mobile/Me/vipSkuList');
                Route::post('refreshSku','mobile/Me/refreshSkuList');
                Route::post('buyRefresh','mobile/Me/buyRefresh');
                Route::post('certification','mobile/Me/certification');
                Route::post('follow','mobile/Me/follow');
                Route::post('unFollow','mobile/Me/unFollow');
            });

        })->middleware(\app\mobile\middleware\AuthMiddleware::class);

    });

    //管理端
    Route::group('admin', function () {

        //登录
        Route::post('login','admin/Auth/login');

        //需要授权
        Route::group('', function () {

            //系统参数
            Route::post('system','admin/System/detail');
            Route::post('welcome/detail','admin/Home/welcomeDetail');

            //配置管理
            Route::group('setting', function () {
                Route::post('basic','admin/Setting/basic')->middleware('AdminPermissionMiddleware:setting@basic');
                Route::post('payment','admin/Setting/payment')->middleware('AdminPermissionMiddleware:setting@payment');
                Route::post('sms','admin/Setting/sms')->middleware('AdminPermissionMiddleware:setting@sms');
                Route::post('upload','admin/Setting/upload')->middleware('AdminPermissionMiddleware:setting@upload');
                Route::post('vip','admin/Setting/vip')->middleware('AdminPermissionMiddleware:setting@vip');
                Route::post('user','admin/Setting/user')->middleware('AdminPermissionMiddleware:setting@user');
                Route::post('withdraw','admin/Setting/withdraw')->middleware('AdminPermissionMiddleware:setting@withdraw');
                Route::post('ranking','admin/Setting/ranking')->middleware('AdminPermissionMiddleware:setting@ranking');
                Route::post('commission','admin/Setting/commission')->middleware('AdminPermissionMiddleware:setting@commission');
                Route::post('money','admin/Setting/money')->middleware('AdminPermissionMiddleware:setting@money');
                Route::post('novice_reward/list','admin/Setting/novice_reward_list')->middleware('AdminPermissionMiddleware:setting@novice_reward');
                Route::post('novice_reward/update','admin/Setting/novice_reward_update')->middleware('AdminPermissionMiddleware:setting@novice_reward');
                Route::post('friend_award','admin/Setting/friend_award')->middleware('AdminPermissionMiddleware:setting@friend_award');
                Route::post('refresh_number/list','admin/Setting/refresh_number_list')->middleware('AdminPermissionMiddleware:setting@refresh_number');
                Route::post('refresh_number/update','admin/Setting/refresh_number_update')->middleware('AdminPermissionMiddleware:setting@refresh_number');
            });

            //上传
            Route::group('upload', function () {
                Route::post('image/base64','service/Upload/image_base64');
                Route::post('image/kindeditor','service/Upload/kindeditorImage');
                Route::post('images','service/Upload/images');
                Route::post('videos','service/Upload/videos');
                Route::post('voices','service/Upload/voices');
                Route::post('excels','service/Upload/excels');
            });

            //帮助管理
            Route::group('help_category', function () {
                Route::post('list','admin/HelpCategory/lists')->middleware('AdminPermissionMiddleware:help_category@read');
                Route::get('detail','admin/HelpCategory/detail')->middleware('AdminPermissionMiddleware:help_category@read');
                Route::get('create','admin/HelpCategory/createView')->middleware('AdminPermissionMiddleware:help_category@create');
                Route::post('create','admin/HelpCategory/create')->middleware('AdminPermissionMiddleware:help_category@create');
                Route::get('update','admin/HelpCategory/updateView')->middleware('AdminPermissionMiddleware:help_category@update');
                Route::post('update','admin/HelpCategory/update')->middleware('AdminPermissionMiddleware:help_category@update');
                Route::post('delete','admin/HelpCategory/delete')->middleware('AdminPermissionMiddleware:help_category@delete');
            });

            //帮助管理
            Route::group('help', function () {
                Route::post('list','admin/Help/lists')->middleware('AdminPermissionMiddleware:help@read');
                Route::get('detail','admin/Help/detail')->middleware('AdminPermissionMiddleware:help@read');
                Route::get('create','admin/Help/createView')->middleware('AdminPermissionMiddleware:help@create');
                Route::post('create','admin/Help/create')->middleware('AdminPermissionMiddleware:help@create');
                Route::get('update','admin/Help/updateView')->middleware('AdminPermissionMiddleware:help@update');
                Route::post('update','admin/Help/update')->middleware('AdminPermissionMiddleware:help@update');
                Route::post('delete','admin/Help/delete')->middleware('AdminPermissionMiddleware:help@delete');
            });

            //活动分类管理
            Route::group('activity_category', function () {
                Route::post('list','admin/ActivityCategory/lists')->middleware('AdminPermissionMiddleware:activity_category@read');
                Route::get('detail','admin/ActivityCategory/detail')->middleware('AdminPermissionMiddleware:activity_category@read');
                Route::get('create','admin/ActivityCategory/createView')->middleware('AdminPermissionMiddleware:activity_category@create');
                Route::post('create','admin/ActivityCategory/create')->middleware('AdminPermissionMiddleware:activity_category@create');
                Route::get('update','admin/ActivityCategory/updateView')->middleware('AdminPermissionMiddleware:activity_category@update');
                Route::post('update','admin/ActivityCategory/update')->middleware('AdminPermissionMiddleware:activity_category@update');
                Route::post('delete','admin/ActivityCategory/delete')->middleware('AdminPermissionMiddleware:activity_category@delete');
            });

            //活动管理
            Route::group('activity', function () {
                Route::post('list','admin/Activity/lists')->middleware('AdminPermissionMiddleware:activity@read');
                Route::get('detail','admin/Activity/detail')->middleware('AdminPermissionMiddleware:activity@read');
                Route::get('audit','admin/Activity/audit_view')->middleware('AdminPermissionMiddleware:activity@audit');
                Route::post('audit','admin/Activity/audit')->middleware('AdminPermissionMiddleware:activity@audit');
                Route::post('stop','admin/Activity/stop');
            });

            //任务管理
            Route::group('task', function () {
                Route::post('list','admin/Task/lists')->middleware('AdminPermissionMiddleware:task@read');
                Route::get('detail','admin/Task/detail')->middleware('AdminPermissionMiddleware:task@read');
                Route::get('referee','admin/Task/referee_view')->middleware('AdminPermissionMiddleware:task@referee');
                Route::post('referee','admin/Task/referee')->middleware('AdminPermissionMiddleware:task@referee');
                Route::post('stop','admin/Task/stop');
            });

            //单页管理
            Route::group('page', function () {
                Route::post('list','admin/Page/lists')->middleware('AdminPermissionMiddleware:page@read');
                Route::get('detail','admin/Page/detail')->middleware('AdminPermissionMiddleware:page@read');
                Route::get('create','admin/Page/createView')->middleware('AdminPermissionMiddleware:page@create');
                Route::post('create','admin/Page/create')->middleware('AdminPermissionMiddleware:page@create');
                Route::get('update','admin/Page/updateView')->middleware('AdminPermissionMiddleware:page@update');
                Route::post('update','admin/Page/update')->middleware('AdminPermissionMiddleware:page@update');
                Route::post('delete','admin/Page/delete')->middleware('AdminPermissionMiddleware:page@delete');
            });

            //公告管理
            Route::group('notice', function () {
                Route::post('list','admin/Notice/lists');
                Route::get('create','admin/Notice/createView');
                Route::get('update','admin/Notice/updateView');
                Route::post('create','admin/Notice/create');
                Route::post('update','admin/Notice/update');
                Route::post('delete','admin/Notice/delete');
            });

            //广告管理
            Route::group('a', function () {
                Route::post('list','admin/Ad/lists')->middleware('AdminPermissionMiddleware:ad@read');
                Route::get('detail','admin/Ad/detail')->middleware('AdminPermissionMiddleware:ad@read');
                Route::get('create','admin/Ad/createView')->middleware('AdminPermissionMiddleware:ad@create');
                Route::post('create','admin/Ad/create')->middleware('AdminPermissionMiddleware:ad@create');
                Route::get('update','admin/Ad/updateView')->middleware('AdminPermissionMiddleware:ad@update');
                Route::post('update','admin/Ad/update')->middleware('AdminPermissionMiddleware:ad@update');
                Route::post('delete','admin/Ad/delete')->middleware('AdminPermissionMiddleware:ad@delete');
            });

            //我的
            Route::group('me', function () {
                Route::post('setting','admin/Me/setting');
                Route::post('password','admin/Me/password');
            });

            //用户管理
            Route::group('user', function () {
                Route::post('search','admin/User/search');
                Route::post('list','admin/User/lists')->middleware('AdminPermissionMiddleware:user@read');
                Route::get('detail','admin/User/detail')->middleware('AdminPermissionMiddleware:user@read');
                Route::get('certification/audit','admin/User/certification_audit_view')->middleware('AdminPermissionMiddleware:user@audit');
                Route::post('certification/audit','admin/User/certification_audit')->middleware('AdminPermissionMiddleware:user@audit');
                Route::get('vip/update','admin/User/vip_update_view')->middleware('AdminPermissionMiddleware:user@vip');
                Route::post('vip/update','admin/User/vip_update')->middleware('AdminPermissionMiddleware:user@vip');
                Route::get('balance/update','admin/User/balance_update_view')->middleware('AdminPermissionMiddleware:user@balance');
                Route::post('balance/update','admin/User/balance_update')->middleware('AdminPermissionMiddleware:user@balance');
                Route::get('deposit/update','admin/User/deposit_update_view')->middleware('AdminPermissionMiddleware:user@deposit');
                Route::post('deposit/update','admin/User/deposit_update')->middleware('AdminPermissionMiddleware:user@deposit');
            });

            //黑名单管理
            Route::group('blacklist', function () {
                Route::post('list','admin/Blacklist/lists')->middleware('AdminPermissionMiddleware:blacklist@read');
                Route::get('detail','admin/Blacklist/detail')->middleware('AdminPermissionMiddleware:blacklist@read');
                Route::get('create','admin/Blacklist/create_view')->middleware('AdminPermissionMiddleware:blacklist@create');
                Route::post('create','admin/Blacklist/create')->middleware('AdminPermissionMiddleware:blacklist@create');
                Route::get('update','admin/Blacklist/update_view')->middleware('AdminPermissionMiddleware:blacklist@update');
                Route::post('update','admin/Blacklist/update')->middleware('AdminPermissionMiddleware:blacklist@update');
                Route::post('delete','admin/Blacklist/delete')->middleware('AdminPermissionMiddleware:blacklist@delete');
            });

            //排行榜管理
            Route::group('rankinglist', function () {
                Route::post('list','admin/Rankinglist/lists')->middleware('AdminPermissionMiddleware:rankinglist@read');
                Route::get('detail','admin/Rankinglist/detail')->middleware('AdminPermissionMiddleware:rankinglist@read');
                Route::get('update','admin/Rankinglist/update_view')->middleware('AdminPermissionMiddleware:blacklist@update');
                Route::post('update','admin/Rankinglist/update')->middleware('AdminPermissionMiddleware:blacklist@update');
            });

            //反馈管理
            Route::group('feedback', function () {
                Route::post('list','admin/Feedback/lists')->middleware('AdminPermissionMiddleware:feedback@read');
                Route::get('detail','admin/Feedback/detail')->middleware('AdminPermissionMiddleware:feedback@read');
                Route::get('update','admin/Feedback/update_view')->middleware('AdminPermissionMiddleware:feedback@update');
                Route::post('update','admin/Feedback/update')->middleware('AdminPermissionMiddleware:feedback@update');
            });

            //管理员管理
            Route::group('admin', function () {
                Route::post('list','admin/Admin/lists')->middleware('AdminPermissionMiddleware:admin@read');
                Route::get('detail','admin/Admin/detail')->middleware('AdminPermissionMiddleware:admin@read');
                Route::get('create','admin/Admin/createView')->middleware('AdminPermissionMiddleware:admin@create');
                Route::post('create','admin/Admin/create')->middleware('AdminPermissionMiddleware:admin@create');
                Route::get('update','admin/Admin/updateView')->middleware('AdminPermissionMiddleware:admin@update');
                Route::post('update','admin/Admin/update')->middleware('AdminPermissionMiddleware:admin@update');
                Route::post('delete','admin/Admin/delete')->middleware('AdminPermissionMiddleware:admin@delete');
            });

            //管理员角色管理
            Route::group('admin/role', function () {
                Route::post('list','admin/AdminRole/lists')->middleware('AdminPermissionMiddleware:admin_role@read');
                Route::get('detail','admin/AdminRole/detail')->middleware('AdminPermissionMiddleware:admin_role@read');
                Route::get('create','admin/AdminRole/createView')->middleware('AdminPermissionMiddleware:admin_role@create');
                Route::post('create','admin/AdminRole/create')->middleware('AdminPermissionMiddleware:admin_role@create');
                Route::get('update','admin/AdminRole/updateView')->middleware('AdminPermissionMiddleware:admin_role@update');
                Route::post('update','admin/AdminRole/update')->middleware('AdminPermissionMiddleware:admin_role@update');
                Route::get('access','admin/AdminRole/access_view')->middleware('AdminPermissionMiddleware:admin_role@access');
                Route::post('access','admin/AdminRole/access')->middleware('AdminPermissionMiddleware:admin_role@access');
                Route::post('delete','admin/AdminRole/delete')->middleware('AdminPermissionMiddleware:admin_role@delete');
            });


            //提现列表
            Route::group('wallet/withdraw', function () {
                Route::post('list','admin/WalletWithdraw/lists')->middleware('AdminPermissionMiddleware:finance@read');
                Route::get('detail','admin/WalletWithdraw/detail')->middleware('AdminPermissionMiddleware:finance@read');
                Route::get('audit','admin/WalletWithdraw/auditView')->middleware('AdminPermissionMiddleware:finance@audit');
                Route::post('audit','admin/WalletWithdraw/audit')->middleware('AdminPermissionMiddleware:finance@audit');
            });


        })->middleware(\app\admin\middleware\AuthMiddleware::class);

    });

})->ext('json')->allowCrossDomain();


Route::group('admin', function () {

    //登录
    Route::get('login','admin/Auth/index');

    //验证码
    Route::group('captcha', function () {
        Route::get('image','admin/Captcha/image');
        Route::get('sms','admin/Captcha/sms');
    });

    //需要授权
    Route::group('', function () {

        //配置管理
        Route::group('setting', function () {
            Route::get('basic','admin/Setting/basic_view');
            Route::get('payment','admin/Setting/payment_view');
            Route::get('sms','admin/Setting/sms_view');
            Route::get('upload','admin/Setting/upload_view');
            Route::get('vip','admin/Setting/vip_view');
            Route::get('sms','admin/Setting/sms_view');
            Route::get('withdraw','admin/Setting/withdraw_view');
            Route::get('ranking','admin/Setting/ranking_view');
            Route::get('commission','admin/Setting/commission_view');
            Route::get('money','admin/Setting/money_view');
            Route::get('novice_reward','admin/Setting/novice_reward_view');
            Route::get('friend_award','admin/Setting/friend_award_view');
            Route::get('refresh_number','admin/Setting/refresh_number_view');
        });

        //帮助分类管理
        Route::group('help_category', function () {
            Route::get('list','admin/HelpCategory/index');
        });

        //帮助管理
        Route::group('help', function () {
            Route::get('list','admin/Help/index');
        });

        //活动分类管理
        Route::group('activity_category', function () {
            Route::get('list','admin/ActivityCategory/index');
        });

        //活动管理
        Route::group('activity', function () {
            Route::get('list','admin/Activity/index');
        });

        //任务管理
        Route::group('task', function () {
            Route::get('list','admin/Task/index');
        });

        //单页管理
        Route::group('page', function () {
            Route::get('list','admin/Page/index');
        });

        //公告管理
        Route::group('notice', function () {
            Route::get('list','admin/Notice/index');
        });

        //广告管理
        Route::group('a', function () {
            Route::get('list','admin/Ad/index');
        });

        //我的
        Route::group('me', function () {
            Route::get('setting','admin/Me/settingView');
            Route::get('password','admin/Me/passwordView');
        });

        //用户管理
        Route::group('user', function () {
            Route::get('list','admin/User/index');
        });

        //黑名单管理
        Route::group('blacklist', function () {
            Route::get('list','admin/Blacklist/index');
        });

        //排行榜管理
        Route::group('rankinglist', function () {
            Route::get('list','admin/Rankinglist/index');
        });

        //反馈管理
        Route::group('feedback', function () {
            Route::get('list','admin/Feedback/index');
        });

        //管理员管理
        Route::group('admin', function () {
            Route::get('list','admin/Admin/index');
        });

        //管理员角色管理
        Route::group('admin/role', function () {
            Route::get('list','admin/AdminRole/index');
        });

        //提现列表
        Route::group('wallet/withdraw', function () {
            Route::get('list','admin/WalletWithdraw/index');
        });

        Route::get('index','admin/Home/index');
        Route::get('welcome','admin/Home/welcome');

    })->middleware(\app\admin\middleware\AuthMiddleware::class);

    Route::miss(function() {
        //return Responses::data(40401,'接口不存在');
    });

})->middleware(\app\admin\middleware\CommonMiddleware::class)
    ->ext('shtml');


Route::group('install', function () {

    //安装
    Route::get('index','install/Index/index');

})->ext('html');


return [

];

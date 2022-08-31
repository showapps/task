<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
use app\common\model\UserFollowModel;
use think\facade\Cache;
use Tools\DbConfig;
use Tools\Oss;

/**
 * 数据列表
 *
 * @return int
 * */
function static_version(){
    return env('static.version',1);
}


/**
 * dbConfig
 *
 * @param string $name
 * @param mixed $default
 * @return mixed
 * */
function dbConfig(string $name,$default = null){
    $dbConfig = new DbConfig;
    return $dbConfig->get($name,$default);
}




/**
 * dbConfig
 *
 * @param array $params [['name','default val'][,..]]
 * @param mixed $default
 * @return array
 * */
function dbConfigs($params){
    return (new DbConfig)->getList($params);
}


function clear_db_config_cache(){

    (new DbConfig)->reset();
    Cache::set('db_config_plus',null,1);
    Cache::set('db_config_plus_mobile',null,1);
    Cache::set('db_config_plus_admin',null,1);

}



//检查模块权限
function checkAdminPermissionByModule(\Tools\AdminPermission $adminPermission,$module_name){
    $adminPermission->setModuleName($module_name);
    return (bool)$adminPermission->checkModuleAccess();
}


//检查节点权限
function checkAdminPermissionByAction(\Tools\AdminPermission $adminPermission,$module_name,$node_name){
    $adminPermission->setModuleName($module_name);
    $adminPermission->setActionName($node_name);
    return (bool)$adminPermission->checkActionAccess();
}


//检查模块权限(多个)
function checkAdminPermissionByModules(\Tools\AdminPermission $adminPermission,$module_names){

    foreach ($module_names as $module_name){
        $adminPermission->setModuleName($module_name);
        if($adminPermission->checkModuleAccess() === true){
            return true;
        }
    }
    return false;
}


//检查节点权限（多个）
function checkAdminPermissionByActions(\Tools\AdminPermission $adminPermission,$nodes){


    foreach ($nodes as $node){
        $adminPermission->setModuleName($node[0]);
        $adminPermission->setActionName($node[1]);
        if($adminPermission->checkActionAccess() === true){
            return true;
        }
    }
    return false;
}

function adminRoleUpdataVersion($role_id,$version = null)
{
    $cacheKey = 'admin_role_updata_version_'.$role_id;
    if(is_null($version)){
        return (int)\think\facade\Cache::get($cacheKey,0);
    }else{
        return \think\facade\Cache::set($cacheKey,$version,86400);
    }
}



/**
 * 组装分页数据
 * @param array $lists
 * @return array
 * */
function get_list_pages($lists)
{
    return [
        'total'=>(int)$lists['total'],
        'page'=>(int)$lists['current_page'],
        'size'=>(int)$lists['per_page'],
        'last'=>(int)$lists['last_page'],
    ];
}



/**
　　* 下划线转驼峰
　　**/
function camelize($uncamelized_words,$separator='_')
{
    $uncamelized_words = $separator. str_replace($separator, " ", strtolower($uncamelized_words));
    return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator );
}

/**
　　* 驼峰命名转下划线命名
　　**/
function uncamelize($camelCaps,$separator='_')
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
}


/**
 * 密码加密
 * @param string $pass 密码
 * @param string $key 秘钥
 * @return string
 */
function password_encrypt($pass,$key = 'user'){

    $pass = md5($pass);
    $key = md5($key);

    $passs = str_split($pass);
    $keys = str_split($key);

    return md5($pass.'&'.implode('.',array_diff($passs,$keys)));

}

function check_sms_code($id,$phone,$code){

    $cache = \think\facade\Cache::get('captcha_sms_'.$id,[]);

    if((!isset($cache['code'])) || !isset($cache['phone'])){
        return false;
    }
    if($cache['code'] != $code || $cache['phone'] != $phone){
        return false;
    }

    return true;

}

function delete_sms_code($id){
    cache('captcha_sms_'.$id, NULL);
}

function check_image_code($id,$code){

    $cache = \think\facade\Cache::get('captcha_image_'.$id,'');

    if($cache != $code){
        return false;
    }

    return true;

}

function delete_image_code($id){
    cache('captcha_image_'.$id, NULL);
}




/**
 * Url生成
 * @param string        $url 路由地址
 * @param array  $vars 变量
 * @param bool|string   $suffix 生成的URL后缀
 * @param bool|string   $domain 域名
 * @return string
 */
function admin_url($url = '', array $vars = [])
{
    $url = '/admin'. (!empty($url) ? '/'.$url : '') .'.shtml';
    $vars['_v'] = isset($vars['_v']) ? $vars['_v'] : env('admin.version',1);

    $url = $vars && is_array($vars) ? $url.'?'.http_build_query($vars) : $url;

    return $url;
}




/**
 * Url生成
 * @param string        $url 路由地址
 * @param array  $vars 变量
 * @param bool|string   $suffix 生成的URL后缀
 * @param bool|string   $domain 域名
 * @return string
 */
function install_url($url = '', array $vars = [])
{
    $url = '/install'. (!empty($url) ? '/'.$url : '') .'.shtml';
    $vars['_v'] = isset($vars['_v']) ? $vars['_v'] : env('install.version',1);

    $url = $vars && is_array($vars) ? $url.'?'.http_build_query($vars) : $url;

    return $url;
}



/**
 * Url生成
 * @param string        $url 路由地址
 * @param array  $vars 变量
 * @param bool|string   $suffix 生成的URL后缀
 * @param bool|string   $domain 域名
 * @return string
 */
function tab_admin_url($url = '', array $vars = [])
{
    $url = '/admin'. (!empty($url) ? '/'.$url : '') .'.shtml';
    $vars['_v'] = isset($vars['_v']) ? $vars['_v'] : env('admin.version',1);

    $url = $vars && is_array($vars) ? $url.'?'.http_build_query($vars) : $url;

    return $url;
}



/**
 * Url生成
 * @param string        $url 路由地址
 * @param array  $vars 变量
 * @param bool|string   $domain 域名
 * @return string
 */
function admin_api_url($url = '', array $vars = [])
{
    $url = '/api/admin'. (!empty($url) ? '/'.$url : '') .'.json';
    $vars['_v'] = time();

    $url = $vars && is_array($vars) ? $url.'?'.http_build_query($vars) : $url;
    return $url;
}



/**
 * Url生成
 * @param string        $url 路由地址
 * @param array  $vars 变量
 * @param bool|string   $domain 域名
 * @return string
 */
function install_api_url($url = '', array $vars = [])
{
    $url = '/api/install'. (!empty($url) ? '/'.$url : '') .'.json';
    $vars['_v'] = time();

    $url = $vars && is_array($vars) ? $url.'?'.http_build_query($vars) : $url;
    return $url;
}




/**
 * 随机订单号码
 *
 * @return string
 */
function get_rand_order_no()
{
    return date('ymdHi').mt_rand(111,999).mt_rand(111,999);
}




/**
 * 随机支付流水
 *
 * @return string
 */
function get_trade_no()
{
    return date('ymdHi').mt_rand(111,999).mt_rand(111,999);
}





/**
 * 获取一个推广码
 *
 * @return string
 */
function get_invitation_code($id)
{

    $list = [
        'E','F','G','B','S','T','U','W','Y','C',
        'A','P','D','J','K','L','N','V','U','H',
        9,1,3,7,2,5,0,6,4,8,
    ];

    $code = '';
    if(strlen($id) < 6){
        $code .= 'X';
        if(strlen($id) == 1){
            $id = $id.mt_rand(1111,9999);
        }else if(strlen($id) == 2){
            $id = $id.mt_rand(111,999);
        }else if(strlen($id) == 3){
            $id = $id.mt_rand(11,99);
        }else if(strlen($id) == 4){
            $id = $id.mt_rand(1,9);
        }
    }

    $idList = str_split($id);
    foreach ($idList as $col){
        $rand = rand(0,2);
        $key = $rand == 0 ? $col : ($rand * 10) + $col;
        $code .=  $list[$key];
    }

    return $code;
}

function jiao_to_int($money)
{
    return floor($money * 10);
}

function jiao_to_float($money)
{
    return $money / 10;
}

function fen_to_int($money)
{
    return floor($money * 100);
}

function fen_to_float($money)
{
    return $money / 100;
}

function li_to_int($float)
{
    return floor($float * 1000);
}

function li_to_float($int)
{
    return $int / 1000;
}

function hao_to_int($float)
{
    return floor($float * 10000);
}

function hao_to_float($int)
{
    return $int / 10000;
}


function get_rand_union_id()
{
    return '_'.time().mt_rand(111,999).mt_rand(111,999).mt_rand(111,999);
}


function is_follow($user_id,$followed_id){
    return (bool)UserFollowModel::where('user_id','=',$user_id)
        ->where('followed_user_id','=',$followed_id)
        ->count();
}


function get_end_info($end_dt,$time = NULL){

    $time = is_null($time) ? time() : $time;
    $info = [
        'day'=>0,
        'hour'=>0,
        'minute'=>0,
        'second'=>0,
    ];
    $end_tiem = strtotime($end_dt);
    $diff = $end_tiem - $time;
    if($diff > 0){
        $info['day'] = floor($diff / (60 * 60 * 24));
        $info['hour'] = floor($diff / (60 * 60)) - ($info['day'] * 24);
        $info['minute'] = floor($diff / 60) - ($info['day'] * 24 * 60) - ($info['hour'] * 60);
        $info['second'] = floor($diff) - ($info['day'] * 24 * 60 * 60) - ($info['hour'] * 60 * 60) - ($info['minute'] * 60);
    }

    return $info;
}





/**
 * 修改 env文件
 * @param string $file 文件路径
 * @param array  $data 配置数组
 * @return bool
 */
function modEnv($file,array $data = []){

    $dotenv = \Dotenv\Dotenv::create(env('root_path'), '.env');
    $configs = $dotenv->load();

    if($data){
        foreach ($data as $key=>$val){
            $configs[$key] = $val;
        }
    }

    $contents = [];
    if($configs){
        foreach ($configs as $key=>$config){

            if(in_array($config,['true','false'])){
                $config = is_string($config) ? ''.$config.'' : $config;
            }else{
                $config = is_string($config) ? '"'.$config.'"' : $config;
            }

            $contents[] = $key.'='.$config;
        }
    }

    return file_put_contents($file.'.env',implode("\r",$contents));
}


function in_black($user_id,$id){

    $info = \app\common\model\BlacklistModel::where('user_id','=',$user_id)
        ->order('end_dt DESC')
        ->find();

    if(!$info){
        return false;
    }

    if(strtotime($info['end_dt']) < strtotime(date('Y-m-d'))){
        return false;
    }

    return in_array($id,$info['authoritys']);

}


function get_task_commission_calc($money,$user){

    $commission_rate = dbConfig('commission.task_rate',0);

    //超级商人
    if($user['merchant_level'] == 1 || $user['user_level'] == 1 ){
        $commission_rate = dbConfig('commission.task_vip_rate',0);
    }

    return $money * (fen_to_float($commission_rate) / 100);

}


function get_task_commission($money,$commission_rate = NULL){

    if(is_null($commission_rate)){
        $commission_rate = dbConfig('commission.task_rate',0);
    }

    return $money * (fen_to_float($commission_rate) / 100);

}


function get_task_vip_commission($money,$commission_rate = NULL){

    if(is_null($commission_rate)){
        $commission_rate = dbConfig('commission.task_vip_rate',0);
    }

    return $money * (fen_to_float($commission_rate) / 100);

}

function create_poster_qrcode($invitation_code){

    $ossConfig = dbConfig('oss');
    $link = dbConfig('mobile_mp.link','').'/?share_code='.$invitation_code;
    $fileName = md5($link).'.png';
    $filePath = 'static/cache/qrcode/'.$fileName;
    $savePath = 'task/uploads/share_codes/'.$fileName;
    if(!file_exists(env('root_path').'public/'.$filePath.'.lock')){
        $qrCode = new \Endroid\QrCode\QrCode($link);

        $oss = Oss::instance($ossConfig);
        $result = $oss->push($savePath,$qrCode->writeString());
        if($result){
            return $ossConfig['domain_header'].'/'.$savePath;
        }

    }

    return $ossConfig['domain_header'].'/'.$savePath;

}



function check_id_crad_front($image){

    $config = dbConfig('idcrad_verify',[
        'app_key'=>'203803447',
        'app_secret'=>'ylnxoxtdmhapirobop70apti8vb2b3e0',
        'app_code'=>'8da8e077c5bf41c0b9a1f0448c5d1a83',
    ]);

    //未开启
    if((!isset($config['app_key'])) || (!isset($config['app_code']))){
        return ['code'=>400,'message'=>'平台未开启认证功能'];
    }

    $host = "https://ocridcard.market.alicloudapi.com";
    $path = "/idCardAuto";
    $method = "POST";
    $appcode = $config['app_code'];//开通服务后 买家中心-查看AppCode
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);
    //根据API的要求，定义相对应的Content-Type
    array_push($headers, "Content-Type" . ":" . "application/x-www-form-urlencoded; charset=UTF-8");
    $querys = "";
    $bodys = "image=".$image;
    $url = $host . $path;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);

    if (1 == strpos("$" . $host, "https://")) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
    $out_put = curl_exec($curl);

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    list($header, $body) = explode("\r\n\r\n", $out_put, 2);
    if ($httpCode == 200) {

        $data = json_decode($body,true);
        //dump($data);
        switch ($data['code']){
            case 1://身份证正面
                return ['code'=>200,'message'=>'success','data'=>$data['result']];
                break;
            case 2://身份证正面
                return ['code'=>400,'message'=>'请上传正面图片'];
                break;
            default:
                return ['code'=>400,'message'=>$data['msg']];
        }

        return ['code'=>200,'message'=>'success','data'=>$body];
    } else {
        if ($httpCode == 400 && strpos($header, "Invalid Param Location") !== false) {
            return ['code'=>400,'message'=>'识别失败：参数错误'];
        } elseif ($httpCode == 400 && strpos($header, "Invalid AppCode") !== false) {
            return ['code'=>400,'message'=>'识别失败：AppCode错误'];
        } elseif ($httpCode == 400 && strpos($header, "Invalid Url") !== false) {
            return ['code'=>400,'message'=>'识别失败：Method、Path 或者环境错误'];
        } elseif ($httpCode == 403 && strpos($header, "Unauthorized") !== false) {
            return ['code'=>400,'message'=>'识别失败：服务未被授权（或URL和Path不正确）'];
        } elseif ($httpCode == 403 && strpos($header, "Quota Exhausted") !== false) {
            return ['code'=>400,'message'=>'识别失败：套餐包次数用完'];
        } elseif ($httpCode == 500) {
            return ['code'=>400,'message'=>'识别失败：API网关错误'];
        } elseif ($httpCode == 0) {
            return ['code'=>400,'message'=>'识别失败：URL错误'];
        } else {
            return ['code'=>400,'message'=>'识别失败：参数名错误 或 其他错误'];
        }
    }
}



function check_id_crad_reverse($image){

    $config = dbConfig('idcrad_verify',[
        'app_key'=>'203803447',
        'app_secret'=>'ylnxoxtdmhapirobop70apti8vb2b3e0',
        'app_code'=>'8da8e077c5bf41c0b9a1f0448c5d1a83',
    ]);

    //未开启
    if((!isset($config['app_key'])) || (!isset($config['app_code']))){
        return ['code'=>400,'message'=>'平台未开启认证功能'];
    }

    $host = "https://ocridcard.market.alicloudapi.com";
    $path = "/idCardAuto";
    $method = "POST";
    $appcode = $config['app_code'];//开通服务后 买家中心-查看AppCode
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);
    //根据API的要求，定义相对应的Content-Type
    array_push($headers, "Content-Type" . ":" . "application/x-www-form-urlencoded; charset=UTF-8");
    $querys = "";
    $bodys = "image=".$image;
    $url = $host . $path;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);

    if (1 == strpos("$" . $host, "https://")) {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
    $out_put = curl_exec($curl);

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    list($header, $body) = explode("\r\n\r\n", $out_put, 2);
    if ($httpCode == 200) {

        $data = json_decode($body,true);
        switch ($data['code']){
            case 1://身份证正面
                return ['code'=>400,'message'=>'请上传背面图片'];
                break;
            case 2://身份证正面
                return ['code'=>200,'message'=>'success','data'=>$data['result']];
                break;
            default:
                return ['code'=>400,'message'=>$data['msg']];
        }

        return ['code'=>200,'message'=>'success','data'=>$body];
    } else {
        if ($httpCode == 400 && strpos($header, "Invalid Param Location") !== false) {
            return ['code'=>400,'message'=>'识别失败：参数错误'];
        } elseif ($httpCode == 400 && strpos($header, "Invalid AppCode") !== false) {
            return ['code'=>400,'message'=>'识别失败：AppCode错误'];
        } elseif ($httpCode == 400 && strpos($header, "Invalid Url") !== false) {
            return ['code'=>400,'message'=>'识别失败：Method、Path 或者环境错误'];
        } elseif ($httpCode == 403 && strpos($header, "Unauthorized") !== false) {
            return ['code'=>400,'message'=>'识别失败：服务未被授权（或URL和Path不正确）'];
        } elseif ($httpCode == 403 && strpos($header, "Quota Exhausted") !== false) {
            return ['code'=>400,'message'=>'识别失败：套餐包次数用完'];
        } elseif ($httpCode == 500) {
            return ['code'=>400,'message'=>'识别失败：API网关错误'];
        } elseif ($httpCode == 0) {
            return ['code'=>400,'message'=>'识别失败：URL错误'];
        } else {
            return ['code'=>400,'message'=>'识别失败：参数名错误 或 其他错误'];
        }
    }
}



function check_id_crad($name,$id_number){

    $config = dbConfig('idcrad_verify',[
        'app_key'=>'203803447',
        'app_secret'=>'ylnxoxtdmhapirobop70apti8vb2b3e0',
        'app_code'=>'8da8e077c5bf41c0b9a1f0448c5d1a83',
    ]);

    //未开启
    if((!isset($config['app_key'])) || (!isset($config['app_code']))){
        return ['code'=>400,'message'=>'平台未开启认证功能'];
    }

    $host = "https://zidv2.market.alicloudapi.com";
    $path = "/idcard/VerifyIdcardv2";
    $method = "GET";
    $appcode = $config['app_code'];
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);
    $querys = "cardNo='.$id_number.'&realName=".urlencode($name);
    $bodys = "";
    $url = $host . $path . "?" . $querys;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    if (1 == strpos("$".$host, "https://"))
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    $out_put = curl_exec($curl);

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    list($header, $body) = explode("\r\n\r\n", $out_put, 2);
    if($httpCode == 200){
        $data = json_decode($body,true);
        if($data['error_code'] == '0'){
            return ['code'=>200,'message'=>'success','data'=>$data['result']];
        }else{
            return ['code'=>400,'message'=>'身份证匹配失败'];
        }
    }else{

        return ['code'=>400,'message'=>'识别失败：第三方服务器异常'];
    }

}


function success_msg(message,time) {
    layer.msg(message,{icon:1,time:time < 500 ? 3000 : time})
}

function error_msg(message,time) {
    layer.msg(message,{icon:5,time:time < 500 ? 3000 : time})
}


function ajax_response_filtr(response,configs) {

    var status = new String(response.status);
    switch (status.substring(0, 3) * 1) {
        case 200://请求、操作成功
            break;
        case 400://参数错误、表单验证错误
            error_msg(response.message);
            break;
        case 401://token 无效 或 已过期
            top.location.href = configs.loginUrl;
            break;
        case 403://用户通过了身份验证，但是不具有访问资源所需的权限。
            error_msg(response.message);
            setTimeout(function () {
                top.location.href = configs.indexUrl;
            }, 1000);
            break;
        case 404://访问的资源部存在
            error_msg(response.message);
            break;
        case 405://请求的 Http 方法不允许使用
            error_msg(response.message);
            break;
        case 500://客户端请求有效，服务器处理时发生了意外。
            error_msg(response.message);
            break;
        default:

            error_msg('无效的接口请求或网咯异常');
            setTimeout(function () {
                top.location.href = configs.indexUrl;
            }, 2000);

    }
}


function set_list_filters(data) {
    let json = '{}';
    if(data){
        json = JSON.stringify(data);
    }
    console.log([data,json]);
    document.getElementById('list_filters_box').value = json;
}


function get_list_filters() {
    let json = document.getElementById('list_filters_box').value;
    let data = {};
    if(json){
        data = JSON.parse(json);
    }
    return data;
}


function show_image_box(url,width,height) {

    var template = '';
    template += '<div id="tong" class="hide" style="width: '+width+'px; height: '+height+'px;">';
    template += '<img src="'+url+'" style="max-width: 100%;width: 100%;height: 100%">';
    template += '</div>';

    //页面层-图片
    layer.open({
        type: 1,
        title: false,
        closeBtn: 0,
        area: ['auto'],
        skin: 'layui-layer-nobg', //没有背景色
        shadeClose: true,
        content: template
    });
}


function time() {
    return (new Date()).getTime()/1000;
}


function replaces(string,searchs,replaces) {

    let new_str = string;
    if(searchs && searchs.length >= 1){
        for (let key in searchs){

            let val = '';
            if(replaces){
                val = replaces[key];
            }

            new_str = new_str.replace(searchs[key],val);
        }
    }
    return new_str;
}
<?php
/**
 * ErrorCode.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2019-11-08
 */

namespace WechatDecode;

class ErrorCode
{
    public static $OK = 0;
    public static $IllegalAesKey = -41001;
    public static $IllegalIv = -41002;
    public static $IllegalBuffer = -41003;
    public static $DecodeBase64Error = -41004;
}
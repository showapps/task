<?php
/**
 * MobileErrorException.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/11
 */

namespace app\common\exception;


class MobileErrorException extends \Exception
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous = null);
    }
}
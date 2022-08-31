<?php
/**
 * DbException.php
 * @author  hexiang
 * @email  itjackhe@163.com
 * @date  2020/6/2
 */

namespace app\common\exception;


class DbException extends \Exception
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous = null);
    }
}
<?php

namespace app\common\exception;


class SystemNotInstallException extends \Exception
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous = null);
    }
}
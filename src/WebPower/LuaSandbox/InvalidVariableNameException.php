<?php
namespace WebPower\LuaSandbox;

class InvalidVariableNameException extends Exception
{
    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        $message = 'Invalid variable name: ' . $message;
        parent::__construct($message, $code, $previous);
    }

}

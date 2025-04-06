<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

// 51
class MessageDNUException extends IPPException
{
    public function __construct(string $message = "Do not understand message", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::INTERPRET_DNU_ERROR, $previous);
    }
}

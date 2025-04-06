<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

// 53
class IncorrectArgumentException extends IPPException
{
    public function __construct(string $message = "Incorrect Argument Exception", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::INTERPRET_VALUE_ERROR, $previous);
    }
}

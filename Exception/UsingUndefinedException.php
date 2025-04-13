<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

// 32
class UsingUndefinedException extends IPPException
{
    public function __construct(string $message = "Using undefined variable/class/parameter/...", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::PARSE_UNDEF_ERROR, $previous);
    }
}

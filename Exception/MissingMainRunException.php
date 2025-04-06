<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

// 31
class MissingMainRunException extends IPPException
{
    public function __construct(string $message = "Missing Main class or Run method", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::PARSE_MAIN_ERROR, $previous);
    }
}

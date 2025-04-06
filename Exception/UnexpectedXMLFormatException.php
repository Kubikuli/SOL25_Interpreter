<?php

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

// 42
class UnexpectedXMLFormatException extends IPPException
{
    public function __construct(string $message = "Unexpected XML Format", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::INVALID_SOURCE_STRUCTURE_ERROR, $previous);
    }
}

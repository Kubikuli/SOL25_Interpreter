<?php

/**
 * VUT FIT - IPP
 * @author Jakub Lůčný (xlucnyj00)
 * @date 2025-04-14
 * @project IPP project 2 - interpreter for SOL25 language
 * @brief UnexpectedXMLFormatException exception class definition
 */

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for invalid XML format
 *
 * Return code: 42
 */
class UnexpectedXMLFormatException extends IPPException
{
    public function __construct(string $message = "Unexpected XML Format", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::INVALID_SOURCE_STRUCTURE_ERROR, $previous);
    }
}

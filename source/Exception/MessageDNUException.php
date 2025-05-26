<?php

/**
 * VUT FIT - IPP
 * @author Jakub Lůčný (xlucnyj00)
 * @date 2025-04-14
 * @project IPP project 2 - interpreter for SOL25 language
 * @brief MessageDNUException exception class definition
 */

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for "Do not understand message" error
 *
 * Return code: 51
 */
class MessageDNUException extends IPPException
{
    public function __construct(string $message = "Do not understand message", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::INTERPRET_DNU_ERROR, $previous);
    }
}

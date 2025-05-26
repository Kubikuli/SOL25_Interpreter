<?php

/**
 * VUT FIT - IPP
 * @author Jakub Lůčný (xlucnyj00)
 * @date 2025-04-14
 * @project IPP project 2 - interpreter for SOL25 language
 * @brief UsingUndefinedException exception class definition
 */

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for using undefined variable/class/class method
 *
 * Return code: 32
 */
class UsingUndefinedException extends IPPException
{
    public function __construct(string $message = "Using undefined var/class/param/...", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::PARSE_UNDEF_ERROR, $previous);
    }
}

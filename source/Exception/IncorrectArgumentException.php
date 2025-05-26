<?php

/**
 * VUT FIT - IPP
 * @author Jakub Lůčný (xlucnyj00)
 * @date 2025-04-14
 * @project IPP project 2 - interpreter for SOL25 language
 * @brief IncorrectArgumentException exception class definition
 */

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for incorrect arguments in methods
 *
 * Return code: 53
 */
class IncorrectArgumentException extends IPPException
{
    public function __construct(string $message = "Incorrect Argument Exception", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::INTERPRET_VALUE_ERROR, $previous);
    }
}

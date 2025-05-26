<?php

/**
 * VUT FIT - IPP
 * @author Jakub Lůčný (xlucnyj00)
 * @date 2025-04-14
 * @project IPP project 2 - interpreter for SOL25 language
 * @brief InterpretException exception class definition
 */

namespace IPP\Student\Exception;

use IPP\Core\Exception\IPPException;
use IPP\Core\ReturnCode;
use Throwable;

/**
 * Exception for other errors during interpretation
 *
 * Return code: 52
 */
class InterpretException extends IPPException
{
    public function __construct(string $message = "Interpret Exception", ?Throwable $previous = null)
    {
        parent::__construct($message, ReturnCode::INTERPRET_TYPE_ERROR, $previous);
    }
}

<?php

namespace Flytedan\DanxLaravel\Exceptions;

class ApiException extends \Exception
{
    public function __construct(
        $message,
        $code = 1000,
        $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

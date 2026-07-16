<?php

namespace App\Exceptions;

use RuntimeException;

class ActivationRateLimitedException extends RuntimeException
{
    public function __construct(public readonly int $retryAfter)
    {
        parent::__construct('Too many activation attempts.');
    }
}

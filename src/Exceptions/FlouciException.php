<?php

namespace Flouci\Laravel\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

class FlouciException extends RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly ?Response $response = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $response?->status() ?? 0, $previous);
    }
}

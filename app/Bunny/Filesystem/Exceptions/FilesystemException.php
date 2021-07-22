<?php

namespace App\Bunny\Filesystem\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class FilesystemException extends Exception
{
    public static function fromResponse(ResponseInterface $response): self
    {
        return new self(sprintf(
            'The storage api returns %s which is an invalid status code.',
            $response->getStatusCode()
        ));
    }

    public static function fromPrevious(Exception $exception): self
    {
        return new self($exception->getMessage(), 0, $exception);
    }
}

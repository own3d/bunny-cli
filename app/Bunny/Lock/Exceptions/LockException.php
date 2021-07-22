<?php

namespace App\Bunny\Lock\Exceptions;

use Exception;

class LockException extends Exception
{
    public static function fromInvalidVersion($version): self
    {
        return new self(sprintf('Your lock file version %s is not supported.', $version));
    }
}

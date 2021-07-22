<?php

namespace App\Bunny\Filesystem\Exceptions;

use App\Bunny\Filesystem\File;

class FileNotFoundException extends FilesystemException
{
    public static function fromFile(File $file): self
    {
        return new self(sprintf('The file %s was not found.', $file->getFilename()));
    }
}

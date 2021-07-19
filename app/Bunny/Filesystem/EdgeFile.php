<?php

namespace App\Bunny\Filesystem;

use Illuminate\Support\Str;
use stdClass;

class EdgeFile implements File
{
    private stdClass $file;

    public function __construct(stdClass $file)
    {
        $this->file = $file;
    }

    public function getFilename($search = '', $replace = ''): string
    {
        return Str::replaceFirst($search, $replace, $this->file->Path . $this->file->ObjectName);
    }

    public function isDirectory(): bool
    {
        return $this->file->IsDirectory;
    }

    public function getChecksum(): string
    {
        return $this->file->Checksum;
    }
}

<?php

namespace App\Bunny\Filesystem;

use Illuminate\Support\Str;

class LocalFile implements File
{
    private string $filename;
    private ?string $checksum;

    public function __construct(string $filename, ?string $checksum)
    {
        $this->filename = $filename;
        $this->checksum = $checksum;
    }

    public function getFilename($search = '', $replace = ''): string
    {
        return Str::replaceFirst($search, $replace, $this->filename);
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function isDirectory(): bool
    {
        return $this->checksum == null;
    }
}

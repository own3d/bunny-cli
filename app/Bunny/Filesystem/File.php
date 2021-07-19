<?php

namespace App\Bunny\Filesystem;

interface File
{
    public function getFilename($search = '', $replace = ''): string;

    public function getChecksum(): string;

    public function isDirectory(): bool;
}

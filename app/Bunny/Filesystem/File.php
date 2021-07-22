<?php

namespace App\Bunny\Filesystem;

interface File
{
    public const EMPTY_SHA256 = '0000000000000000000000000000000000000000000000000000000000000000';

    public function getFilename($search = '', $replace = ''): string;

    public function getChecksum(): ?string;

    public function isDirectory(): bool;
}

<?php

namespace App\Bunny\Filesystem;

interface FileSerialized
{
    public function toArray(): array;

    public static function fromArray(array $array): self;
}

<?php

namespace App\Bunny\Filesystem;

interface FileStreamable
{
    /**
     * @return resource|string
     */
    public function getResource();
}

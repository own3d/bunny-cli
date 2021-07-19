<?php

namespace App\Bunny\Filesystem;

class LocalStorage
{
    function allFiles($dir, &$results = array())
    {
        $files = scandir($dir);

        foreach ($files as $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $results[] = new LocalFile($path, strtoupper(hash_file('sha256', $path)));
            } else if ($value != "." && $value != "..") {
                $results[] = new LocalFile($path, null);
                $this->allFiles($path, $results);
            }
        }

        return $results;
    }
}

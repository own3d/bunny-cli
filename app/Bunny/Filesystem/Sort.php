<?php

namespace App\Bunny\Filesystem;

use Illuminate\Support\Str;

class Sort
{

    public static function unique(&$directories): array
    {
        $relevant = [];

        // sort all requested files
        uksort($directories, function (string $a, string $b) {
            $a = count(explode('/', $a));
            $b = count(explode('/', $b));

            if ($a == $b) {
                return 0;
            }
            return ($a < $b) ? -1 : 1;
        });

        // filter all child files and directories
        foreach ($directories as $path => $request) {
            if (!Str::startsWith($path, array_keys($relevant))) {
                $relevant[$path] = $request;
            }
        }

        return $relevant;
    }

    private static function isParentDeleted(array $parents, string $file): bool
    {
        return Str::startsWith($file, $parents);
    }
}

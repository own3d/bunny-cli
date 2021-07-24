<?php

if (!function_exists('bunny_cli_path')) {
    function bunny_cli_path(): string
    {
        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            $result = exec("echo %appdata%");
        } else {
            $result = getenv('HOME');
        }

        return $result . DIRECTORY_SEPARATOR . '.bunny-cli';
    }
}

<?php

namespace App\Traits;

trait WithLocalConfiguration
{
    public function loadLocalConfiguration(string $filename = 'bunny-cli.json')
    {
        $path = getcwd() . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($path)) {
            $config = json_decode(file_get_contents($path), true);
            config()->set('bunny', array_merge_recursive(config('bunny'), $config));
        }
    }
}

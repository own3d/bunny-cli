<?php

namespace App\Commands\Env;

use Dotenv\Dotenv;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;

class EnvSetCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'env:set
                                {key : Key of the environment}
                                {value : Value of the environment}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Set and save an environment variable in the .env file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info(sprintf("The following environment file is used: '%s'", App::environmentFilePath()));

        if (Storage::exists('.env')) {
            $env = Dotenv::parse(Storage::get('.env'));
        } else {
            $this->warn('The environment file does not exist. Creating a new one...');
            $env = [];
        }

        $env[strtoupper($this->argument('key'))] = $this->argument('value');

        Storage::put('.env', self::updateEnv($env));

        $this->info('The environment file was successfully updated.');

        return 0;
    }

    public static function updateEnv($data = []): string
    {
        if (!count($data)) {
            return PHP_EOL;
        }

        $lines = [];

        foreach ($data as $key => $value) {
            if (preg_match('/\s/', $value) || strpos($value, '=') !== false) {
                $value = '"' . $value . '"';
            }

            $lines[] = sprintf('%s=%s', $key, $value);
        }

        return implode(PHP_EOL, $lines);
    }
}

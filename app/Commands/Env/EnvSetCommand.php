<?php

namespace App\Commands\Env;

use Dotenv\Dotenv;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\App;
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
        $envFilePath = App::environmentFilePath();
        $this->info(sprintf("The following environment file is used: '%s'", $envFilePath));

        if (file_exists($envFilePath)) {
            $env = Dotenv::parse(file_get_contents($envFilePath));
        } else {
            $this->warn('The environment file does not exist. Creating a new one...');
            $env = [];
        }

        $env[strtoupper($this->argument('key'))] = $this->argument('value');

        file_put_contents($envFilePath, self::updateEnv($env));

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

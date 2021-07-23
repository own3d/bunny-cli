<?php

namespace App\Commands\Env;

use Dotenv\Dotenv;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\App;
use LaravelZero\Framework\Commands\Command;

class EnvListCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'env:list';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List all current environment variables';

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
            $this->warn('The environment file does not exist.');

            return 1;
        }

        if(empty($env)) {
            $this->warn('The environment file is empty.');

            return 2;
        }

        $this->table(['Key', 'Value'], array_map(fn($k, $v) => [$k, $v], array_keys($env), $env));

        return 0;
    }
}

<?php

namespace App\Commands\Env;

use Dotenv\Dotenv;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
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
        $this->info(sprintf("The following environment file is used: '%s'", App::environmentFilePath()));

        if (Storage::exists('.env')) {
            $env = Dotenv::parse(Storage::get('.env'));
        } else {
            $this->warn('The environment file does not exist.');

            return 1;
        }

        if (empty($env)) {
            $this->warn('The environment file is empty.');

            return 2;
        }

        $this->table(['Key', 'Value'], array_map(fn($k, $v) => [$k, $v], array_keys($env), $env));

        return 0;
    }
}

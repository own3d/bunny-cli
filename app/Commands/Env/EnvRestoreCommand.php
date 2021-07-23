<?php

namespace App\Commands\Env;

use Illuminate\Support\Facades\App;
use LaravelZero\Framework\Commands\Command;

class EnvRestoreCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'env:restore {file : Location of the backup file}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Restore .env file from a given file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $envFilePath = App::environmentFilePath();
        $this->info(sprintf("The following environment file is used: '%s'", $envFilePath));

        file_put_contents($envFilePath, file_get_contents($this->argument('file')));

        $this->info('The environment file was successfully restored.');

        return 0;
    }
}

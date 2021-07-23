<?php

namespace App\Commands\Env;

use Dotenv\Dotenv;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\App;
use LaravelZero\Framework\Commands\Command;

class EnvBackupCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'env:backup {file : Location of the backup file}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Backup .env file into a given file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $envFilePath = App::environmentFilePath();
        $this->info(sprintf("The following environment file is used: '%s'", $envFilePath));

        file_put_contents($this->argument('file'), file_get_contents($envFilePath));

        $this->info('The environment file was successfully backed up.');

        return 0;
    }
}

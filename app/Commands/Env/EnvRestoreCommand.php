<?php

namespace App\Commands\Env;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
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
        $this->info(sprintf("The following environment file is used: '%s'", App::environmentFilePath()));

        Storage::put('.env', Storage::get(sprintf('backups/%s', $this->argument('file'))));

        $this->info('The environment file was successfully restored.');

        return 0;
    }
}

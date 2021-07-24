<?php

namespace App\Commands\Env;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
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
        $this->info(sprintf("The following environment file is used: '%s'", App::environmentFilePath()));

        Storage::put(sprintf('backups/%s', $this->argument('file')), Storage::get('.env'));

        $this->info('The environment file was successfully backed up.');

        return 0;
    }
}

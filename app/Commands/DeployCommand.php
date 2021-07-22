<?php

namespace App\Commands;

use App\Bunny\Filesystem\CompareOptions;
use App\Bunny\Filesystem\EdgeStorage;
use App\Bunny\Filesystem\Exceptions\FilesystemException;
use App\Bunny\Filesystem\FileCompare;
use App\Bunny\Filesystem\LocalStorage;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class DeployCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'deploy
        {--dir=dist : Root directory to upload}
        {--no-sha256-cache : Skips .well-known/bunny.sha256 and queries the storage endpoints recursively instead}
        {--no-sha256-generation : Skips .well-known/bunny.sha256 generation}
        {--sha256-name=.well-known/bunny.sha256 : Change filename of .well-known/bunny.sha256}
        {--dry-run : Outputs the operations but will not execute anything}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Deploy dist folder to edge storage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $start = microtime(true);

        $fileCompare = new FileCompare(
            app(LocalStorage::class),
            app(EdgeStorage::class),
            $this
        );

        $localPath = realpath($path = $this->option('dir') ?? 'dist');

        if (!file_exists($localPath) || !is_dir($localPath)) {
            $this->warn(sprintf('The directory %s does not exists.', $path));
            return 1;
        }

        $edgePath = sprintf('/%s', config('bunny.storage.username'));

        if ($this->option('dry-run')) {
            $this->warn('⚠ Dry run is activated. The operations are displayed but not executed.');
        }

        try {
            $fileCompare->compare($localPath, $edgePath, [
                CompareOptions::START => $start,
                CompareOptions::NO_SHA256_CACHE => $this->option('no-sha256-cache'),
                CompareOptions::NO_SHA256_GENERATION => $this->option('no-sha256-generation'),
                CompareOptions::SHA256_NAME => $this->option('sha256-name'),
                CompareOptions::DRY_RUN => $this->option('dry-run'),
            ]);
        } catch (FilesystemException $exception) {
            $this->error($exception->getMessage());

            return 2;
        }

        return 0;
    }


    /**
     * Define the command's schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}

<?php

namespace App\Commands;

use App\Bunny\Filesystem\CompareOptions;
use App\Bunny\Filesystem\EdgeStorage;
use App\Bunny\Filesystem\Exceptions\FilesystemException;
use App\Bunny\Filesystem\FileCompare;
use App\Bunny\Filesystem\LocalStorage;
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
        {--no-lock-verification : Skips checksum verification from bunny-cli.lock and polls the storage api recursively instead}
        {--no-lock-generation : Skips generation of .well-known/bunny-cli.lock}
        {--lock-file=.well-known/bunny-cli.lock : Changes the location and filename of .well-known/bunny-cli.lock}
        {--dry-run : Outputs the operations but will not execute anything}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Deploy a dist folder to edge storage';

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

        if (!empty(config('bunny.storage.path'))) {
            $edgePath .= '/' . config('bunny.storage.path');
            $this->info(sprintf('- Storage path is set to %s', $edgePath));
        }

        if ($this->option('dry-run')) {
            $this->warn('⚠ Dry run is activated. The operations are displayed but not executed.');
        }

        try {
            $fileCompare->compare($localPath, $edgePath, [
                CompareOptions::START => $start,
                CompareOptions::NO_LOCK_VERIFICATION => $this->option('no-lock-verification'),
                CompareOptions::NO_LOCK_GENERATION => $this->option('no-lock-generation'),
                CompareOptions::LOCK_FILE => $this->option('lock-file'),
                CompareOptions::DRY_RUN => $this->option('dry-run'),
            ]);
        } catch (FilesystemException $exception) {
            $this->error($exception->getMessage());

            return 2;
        }

        return 0;
    }
}

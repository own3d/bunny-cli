<?php

namespace App\Commands;

use App\Bunny\Filesystem\EdgeStorage;
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
        {--dir=dist : Root directory to upload}';

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
        $edgeStorage = new EdgeStorage();
        $localStorage = new LocalStorage();
        $fileCompare = new FileCompare($localStorage, $edgeStorage, $this);

        $localPath = realpath($path = $this->option('dir') ?? 'dist');

        if (!file_exists($localPath) || !is_dir($localPath)) {
            $this->warn(sprintf('The directory %s does not exists.', $path));
            return 1;
        }

        $edgePath = sprintf('/%s', config('bunny.storage.username'));

        $fileCompare->compare($localPath, $edgePath);

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

<?php

namespace App\Commands;

use App\Bunny\Client;
use App\Commands\Env\EnvSetCommand;
use Dotenv\Dotenv;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;

class InitCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'init
        {--api-key= : API key of the Bunny account}
        {--storage-zone= : Name of the storage zone}
        {--pull-zone= : Name of the pull zone zone}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Initialize a new .env file';

    /**
     * Execute the console command.
     *
     * @param Client $client
     * @return int
     */
    public function handle(Client $client): int
    {
        $this->info(sprintf("The following environment file is used: '%s'", App::environmentFilePath()));

        if (Storage::exists('.env')) {
            $env = Dotenv::parse(Storage::get('.env'));
        } else {
            $this->warn('The environment file does not exist. Creating a new one...');
            $env = [];
        }

        $env['BUNNY_API_ACCESS_KEY'] = $this->ask(
            'What is your api key?',
            $this->option('api-key') ?? $env['BUNNY_API_ACCESS_KEY'] ?? null
        );

        config()->set('bunny.api.access_key', $env['BUNNY_API_ACCESS_KEY']);

        $storageZones = new Collection($client->getStorageZones()->getData());

        if (!$this->option('no-interaction')) {
            $storageZones->each(fn($item) => $this->info(sprintf(' - %s', $item->Name)));
        }

        $storageZoneName = $this->anticipate(
            'Which storage zone do you want to use?',
            function ($input) use ($storageZones) {
                return $storageZones->filter(function ($item) use ($input) {
                    // replace stristr with your choice of matching function
                    return false !== stristr($item->Name, $input);
                })->pluck('Name')->toArray();
            },
            $this->option('storage-zone')
        );

        $storageZone = $storageZones->where('Name', '===', $storageZoneName)->first();

        $env['BUNNY_STORAGE_USERNAME'] = $storageZone->Name;
        $env['BUNNY_STORAGE_PASSWORD'] = $storageZone->Password;

        $pullZones = new Collection($storageZone->PullZones);

        if (!$this->option('no-interaction')) {
            $pullZones->each(fn($item) => $this->info(sprintf(' - %s', $item->Name)));
        }

        $firstPullZone = $pullZones->count() > 0 ? $pullZones->first()->Name : null;

        $pullZoneName = $this->anticipate(
            'Which pull zone do you want to use?',
            function ($input) use ($storageZones) {
                return $storageZones->filter(function ($item) use ($input) {
                    // replace stristr with your choice of matching function
                    return false !== stristr($item->Name, $input);
                })->pluck('Name')->toArray();
            },
            $this->option('api-key') ?? $firstPullZone
        );

        $pullZone = $pullZones->where('Name', '===', $pullZoneName)->first();

        $env['BUNNY_PULL_ZONE_ID'] = $pullZone->Id ?? null;

        if (!$pullZone) {
            $this->warn('No pull zone was specified, therefore no pull zone is flushed during deployment.');
        }

        Storage::put('.env', EnvSetCommand::updateEnv($env));

        $this->info('The environment file was successfully updated.');

        return 0;
    }
}

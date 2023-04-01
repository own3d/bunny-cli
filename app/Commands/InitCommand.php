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

        $this->newLine();

        $this->info('In order for the Bunny CLI to work properly you need to store your Bunny CDN API token.');
        $this->info('You can find your API Token in your Account Settings (https://dash.bunny.net/account/settings).');

        do {
            $env['BUNNY_API_ACCESS_KEY'] = $this->ask(
                'What is your API Token?',
                $this->option('api-key') ?? $env['BUNNY_API_ACCESS_KEY'] ?? null
            );

            config()->set('bunny.api.access_key', $env['BUNNY_API_ACCESS_KEY']);

            $result = $client->getStorageZones();

            if (!$result->success()) {
                $this->warn('Your API Token is invalid. Please try again.');
            }

            $storageZones = new Collection($result->getData());
        } while (!$result->success() || $storageZones->isEmpty());

        if (!$this->option('no-interaction')) {
            $this->info('Please select your default storage zone below. This is used for the deploy command.');

            $this->newLine();

            $storageZones->each(fn($item) => $this->info(sprintf(' - %s', $item->Name)));
        }

        do {
            $storageZoneName = $this->anticipate(
                'Which storage zone do you want to use?',
                function ($input) use ($storageZones) {
                    return $storageZones->filter(function ($item) use ($input) {
                        // replace stristr with your choice of matching function
                        return false !== stristr($item->Name, $input);
                    })->pluck('Name')->toArray();
                },
                $this->option('storage-zone') ?? $env['BUNNY_STORAGE_USERNAME'] ?? null
            );

            $storageZone = $storageZones->where('Name', '===', $storageZoneName)->first();

            if (!$storageZone) {
                $this->warn(sprintf('Cannot find storage zone by `%s`. Please check your spelling.', $storageZoneName));
            } else {
                $env['BUNNY_STORAGE_USERNAME'] = $storageZone->Name;
                $env['BUNNY_STORAGE_PASSWORD'] = $storageZone->Password;
            }
        } while ($storageZone === null);

        $pullZones = new Collection($storageZone->PullZones);

        if (!$this->option('no-interaction')) {
            $this->info('Now select your pull zone whose cache you want to flush when the deploy is complete.');

            $this->newLine();

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

        $this->info('The environment file was successfully updated!');

        $this->info('You can view these environment variables at any other time using the <comment>bunny env:list</comment> command.');

        $this->info('If you need help, please check out our documentation: <comment>https://github.com/own3d/bunny-cli</comment>');

        $this->newLine();

        $this->info('Thanks for using Bunny CLI!');

        return 0;
    }
}

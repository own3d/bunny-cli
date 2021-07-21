<?php

namespace App\Bunny\Filesystem;

use App\Bunny\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;

class FileCompare
{
    private const RESERVED_FILENAMES = [];

    private LocalStorage $localStorage;
    private EdgeStorage $edgeStorage;
    private Client $apiClient;
    private Command $command;

    public function __construct(LocalStorage $localStorage, EdgeStorage $edgeStorage, Command $command)
    {
        $this->localStorage = $localStorage;
        $this->edgeStorage = $edgeStorage;
        $this->apiClient = new Client();
        $this->command = $command;
    }

    public function compare(string $local, string $edge, float $start): void
    {
        $this->command->info('- Hashing files...');
        $localFilesAndDirectories = $this->localStorage->allFiles($local);
        $localFiles = array_filter($localFilesAndDirectories, fn(LocalFile $x) => !$x->isDirectory());
        $this->command->info(sprintf('✔ Finished hashing %s files', count($localFiles)));
        $this->command->info('- CDN fetching files and directories (progress is approximately)...');
        $expectedMax = count($localFilesAndDirectories) - count($localFiles);
        $bar = $this->command->getOutput()->createProgressBar($expectedMax);
        $edgeFiles = $this->edgeStorage->allFiles($edge, fn() => $bar->advance());
        $bar->finish();
        $this->command->newLine();
        $this->command->info(sprintf('✔ Finished fetching %s files and directories', count($edgeFiles)));
        $this->command->info('- CDN diffing files and directories...');

        $requestsGroups = ['deletions' => [], 'uploads' => []];

        /** @var LocalFile $localFile */
        foreach ($localFiles as $localFile) {
            $filename = $localFile->getFilename($local);
            if ($match = $this->contains($edgeFiles, $filename, $edge)) {
                if ($match->getChecksum() != $localFile->getChecksum()) {
                    $requestsGroups['uploads'][] = fn() => $this->edgeStorage->put($localFile, $local, $edge);
                }
            } else {
                $requestsGroups['uploads'][] = fn() => $this->edgeStorage->put($localFile, $local, $edge);
            }
        }

        /** @var EdgeFile $edgeFile */
        foreach ($edgeFiles as $edgeFile) {
            $filename = $edgeFile->getFilename($edge);
            if (!$this->contains($localFilesAndDirectories, $filename, $local) && !$this->isReserved($filename)) {
                $requestsGroups['deletions'][$filename] = fn() => $this->edgeStorage->delete($edgeFile);
            }
        }

        $requestsGroups['deletions'] = Sort::unique($requestsGroups['deletions']);

        $this->command->info('✔ Finished diffing files and directories');

        foreach ($requestsGroups as $type => $requests) {
            $operations = count($requests);
            if ($operations > 0) {
                $this->command->info(sprintf('- CDN requesting %s %s', $operations, $type));

                $bar = $this->command->getOutput()->createProgressBar($operations);

                $pool = new Pool($this->edgeStorage->getClient(), $requests, [
                    'concurrency' => 5,
                    'fulfilled' => function (Response $response, $index) use ($bar) {
                        $bar->advance();
                    },
                    'rejected' => function (RequestException $reason, $index) use ($bar) {
                        $bar->advance();

                        if ($this->rejectedDue404Deletion($reason)) {
                            return;
                        }

                        $this->command->warn(sprintf(
                            'Request rejected by bunny.net. Status: %s, Message: %s',
                            $reason->getResponse()->getStatusCode(),
                            $reason->getMessage()
                        ));
                    },
                ]);

                // Initiate the transfers and create a promise
                $promise = $pool->promise();

                $bar->start();
                $promise->wait(); // Force the pool of requests to complete.
                $bar->finish();

                $this->command->newLine();

                $this->command->info(sprintf('✔ Finished synchronizing %s', $type));
            }
        }

        $this->command->info('- Waiting for deploy to go live...');

        $result = $this->apiClient->purgeCache($pullZoneId = config('bunny.pull_zone.id'));

        if (!$result->success()) {
            $this->command->info('✔ Deploy is live (without flush)!');
            return;
        }

        $result = $this->apiClient->getPullZone($pullZoneId);

        $timeElapsedSecs = microtime(true) - $start;

        $this->command->info(sprintf('✔ Deployment is live! (%ss)', number_format($timeElapsedSecs, 2)));
        $this->command->newLine();

        foreach ($result->getData()->Hostnames as $hostname) {
            $schema = ($hostname->ForceSSL || $hostname->HasCertificate) ? 'https' : 'http';
            $this->command->info(sprintf('Website URL: %s://%s', $schema, $hostname->Value));
        }
    }

    private function contains(array $files, string $filename, string $search): ?File
    {
        foreach ($files as $edgeFile) {
            if ($edgeFile->getFilename($search) === $filename) {
                return $edgeFile;
            }
        }

        return null;
    }

    private function isReserved($filename): bool
    {
        return in_array($filename, self::RESERVED_FILENAMES);
    }

    private function rejectedDue404Deletion(RequestException $reason): bool
    {
        return $reason->getRequest()->getMethod() === 'DELETE'
            && in_array($reason->getResponse()->getStatusCode(), [404, 400, 500], true);
    }
}

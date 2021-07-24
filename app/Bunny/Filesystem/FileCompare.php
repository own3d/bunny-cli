<?php

namespace App\Bunny\Filesystem;

use App\Bunny\Client;
use App\Bunny\Filesystem\Exceptions\FileNotFoundException;
use Exception;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;

class FileCompare
{
    private const RESERVED_FILENAMES = [
        '/.well-known',
        '/.well-known/bunny-cli.lock',
    ];

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

    /**
     * @throws Exceptions\FilesystemException
     */
    public function compare(string $local, string $edge, array $options): void
    {
        $this->command->info('- Hashing files...');
        $localFilesAndDirectories = $this->localStorage->allFiles($local);
        $localFiles = array_filter($localFilesAndDirectories, fn(LocalFile $x) => !$x->isDirectory());
        $this->command->info(sprintf('✔ Finished hashing %s files', count($localFiles)));
        $expectedMax = count($localFilesAndDirectories) - count($localFiles);
        $edgeFiles = $this->getEdgeFiles($options, $edge, $expectedMax);
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

                if (!$options[CompareOptions::DRY_RUN]) {
                    // Initiate the transfers and create a promise
                    $promise = $pool->promise();

                    $bar->start();
                    $promise->wait(); // Force the pool of requests to complete.
                    $bar->finish();

                    $this->command->newLine();
                }

                $this->command->info(sprintf('✔ Finished synchronizing %s', $type));
            }
        }

        if (!$options[CompareOptions::NO_LOCK_GENERATION]) {
            $this->command->info('- Generating cache for current deployment...');

            if (!$this->edgeStorage->getStorageCache()->save($local, $edge, $localFilesAndDirectories)) {
                $this->command->info('✔ Cache published successfully.');
            } else {
                $this->command->error('✘ Error publishing cache.');
            }
        }
        $pullZoneId = config('bunny.pull_zone.id');

        $this->command->info('- Waiting for deploy to go live...');

        if (!$options[CompareOptions::DRY_RUN] && $pullZoneId) {
            $flushResult = $this->apiClient->purgeCache($pullZoneId);
        }

        $result = $this->apiClient->getPullZone($pullZoneId);

        $timeElapsedSecs = microtime(true) - $options[CompareOptions::START];
        $message = !isset($flushResult) || !$result->success()
            ? '✔ Deployment is live (without flush)! (%ss)'
            : '✔ Deployment is live! (%ss)';
        $this->command->info(sprintf($message, number_format($timeElapsedSecs, 2)));
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

    /**
     * @throws Exceptions\FilesystemException
     */
    private function getEdgeFiles(array $options, string $edge, int $expectedMax): array
    {
        $this->edgeStorage->getStorageCache()->setFilename($options[CompareOptions::LOCK_FILE]);

        if ($options[CompareOptions::NO_LOCK_VERIFICATION]) {
            return $this->getAllFilesRecursive($expectedMax, $edge);
        }

        try {
            $this->command->info('- CDN fetching files and directories from cache...');
            return $this->edgeStorage->getStorageCache()->parse($edge);
        } catch (FileNotFoundException $exception) {
            $this->command->warn(sprintf(
                '⚠ Cannot fetch %s from storage due "%s". Using recursive fallback...',
                $options[CompareOptions::LOCK_FILE],
                $exception->getMessage()
            ));
            return $this->getAllFilesRecursive($expectedMax, $edge);
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function getAllFilesRecursive(int $expectedMax, string $edge): array
    {
        $this->command->info('- CDN fetching files and directories (progress is approximately)...');
        $bar = $this->command->getOutput()->createProgressBar($expectedMax);
        $result = $this->edgeStorage->allFiles($edge, fn() => $bar->advance());
        $bar->finish();
        $this->command->newLine();
        return $result;
    }
}

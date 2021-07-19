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
    private Command $output;

    public function __construct(LocalStorage $localStorage, EdgeStorage $edgeStorage, Command $output)
    {
        $this->localStorage = $localStorage;
        $this->edgeStorage = $edgeStorage;
        $this->apiClient = new Client();
        $this->output = $output;
    }

    public function compare(string $local, string $edge): void
    {
        $this->output->info('- Hashing files...');
        $localFiles = $this->localStorage->allFiles($local);
        $this->output->info(sprintf('✔ Finished hashing %s files', count($localFiles)));
        $this->output->info('- CDN diffing files...');
        $edgeFiles = $this->edgeStorage->allFiles($edge);

        $requests = [];

        /** @var LocalFile $localFile */
        foreach ($localFiles as $localFile) {
            if ($localFile->isDirectory()) {
                continue;
            }

            $filename = $localFile->getFilename($local);
            if ($match = $this->contains($edgeFiles, $filename, $edge)) {
                if ($match->getChecksum() != $localFile->getChecksum()) {
                    $requests[] = fn() => $this->edgeStorage->put($localFile, $local, $edge);
                }
            } else {
                $requests[] = fn() => $this->edgeStorage->put($localFile, $local, $edge);
            }
        }

        /** @var EdgeFile $edgeFile */
        foreach ($edgeFiles as $edgeFile) {
            $filename = $edgeFile->getFilename($edge);
            if (!$this->contains($localFiles, $filename, $local) && !$this->isReserved($filename)) {
                $requests[] = fn() => $this->edgeStorage->delete($edgeFile);
            }
        }

        $this->output->info(sprintf('✔ CDN requesting %s files', $count = count($requests)));

        if ($count > 0) {
            $this->output->info(sprintf('- Synchronizing %s files', $count));

            $bar = $this->output->getOutput()->createProgressBar($count);

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

                    $this->output->warn(sprintf(
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

            $this->output->newLine();

            $this->output->info(sprintf('✔ Finished synchronizing %s files', $count));
        }

        $this->output->info('- Waiting for deploy to go live...');

        $result = $this->apiClient->purgeCache($pullZoneId = config('bunny.pull_zone.id'));

        if (!$result->success()) {
            $this->output->info('✔ Deploy is live (without flush)!');
            return;
        }

        $result = $this->apiClient->getPullZone($pullZoneId);

        $this->output->info('✔ Deploy is live!');
        $this->output->newLine();

        foreach ($result->getData()->Hostnames as $hostname) {
            $schema = ($hostname->ForceSSL || $hostname->HasCertificate) ? 'https' : 'http';
            $this->output->info(sprintf('Website URL: %s://%s', $schema, $hostname->Value));
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

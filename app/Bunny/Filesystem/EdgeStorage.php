<?php

namespace App\Bunny\Filesystem;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;

class EdgeStorage
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => sprintf('https://%s', config('bunny.storage.hostname')),
        ]);
    }

    public function allFiles(string $path, &$results = array())
    {
        $promise = $this->client->getAsync(self::normalizePath($path, true), [
            RequestOptions::HEADERS => [
                'AccessKey' => config('bunny.storage.password'),
            ],
        ]);

        $promise->then(
            function (ResponseInterface $res) use (&$results) {
                $files = array_map(
                    fn($file) => new EdgeFile($file),
                    json_decode($res->getBody()->getContents(), false)
                );

                foreach ($files as $file) {
                    $results[] = $file;
                    if ($file->isDirectory()) {
                        $this->allFiles($file->getFilename(), $results);
                    }
                }

            },
            function (RequestException $e) {
                echo $e->getMessage() . "\n";
                echo $e->getRequest()->getMethod();
            }
        );

        $promise->wait();

        return $results;
    }

    public function put(LocalFile $file, string $local, string $edge): PromiseInterface
    {
        return $this->client->putAsync(self::normalizePath($file->getFilename($local, $edge), $file->isDirectory()), [
            RequestOptions::HEADERS => [
                'AccessKey' => config('bunny.storage.password'),
                'Checksum' => $file->getChecksum(),
            ],
            RequestOptions::BODY => fopen($file->getFilename(), 'r'),
        ]);
    }

    public function delete(EdgeFile $file): PromiseInterface
    {
        return $this->client->deleteAsync(self::normalizePath($file->getFilename(), $file->isDirectory()), [
            RequestOptions::HEADERS => [
                'AccessKey' => config('bunny.storage.password'),
            ],
        ]);
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    private static function normalizePath(string $filename, bool $isDirectory): string
    {
        if (!Str::startsWith($filename, ['/'])) {
            $filename = '/' . $filename;
        }

        if ($isDirectory && !Str::endsWith($filename, ['/'])) {
            $filename = $filename . '/';
        }

        return $filename;
    }
}

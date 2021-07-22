<?php

namespace App\Bunny\Filesystem;

use App\Bunny\Filesystem\Exceptions\FileNotFoundException;
use App\Bunny\Filesystem\Exceptions\FilesystemException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;

class EdgeStorage
{
    private Client $client;
    private EdgeStorageCache $storageCache;

    public function __construct()
    {
        $this->storageCache = new EdgeStorageCache($this);
        $this->client = new Client([
            'base_uri' => sprintf('https://%s', config('bunny.storage.hostname')),
            'http_errors' => false,
            'headers' => [
                'User-Agent' => 'BunnyCLI/0.1',
            ]
        ]);
    }

    public function allFiles(string $path, callable $advance = null, &$results = array()): array
    {
        $promise = $this->client->getAsync(self::normalizePath($path, true), [
            RequestOptions::HEADERS => [
                'AccessKey' => config('bunny.storage.password'),
            ],
        ]);

        $promise->then(
            function (ResponseInterface $res) use ($advance, &$results) {
                $files = array_map(
                    fn($file) => new EdgeFile($file),
                    json_decode($res->getBody()->getContents(), false)
                );

                foreach ($files as $file) {
                    $results[] = $file;
                    if ($file->isDirectory()) {
                        $this->allFiles($file->getFilename(), $advance, $results);
                    }
                }

                if ($advance) {
                    $advance();
                }
            },
            function (RequestException $e) {
                throw FilesystemException::fromPrevious($e);
            }
        );

        $promise->wait();

        return $results;
    }

    /**
     * @throws FilesystemException
     */
    public function get(EdgeFile $file): string
    {
        try {
            $response = $this->client->get(self::normalizePath($file->getFilename(), $file->isDirectory()), [
                RequestOptions::HEADERS => [
                    'AccessKey' => config('bunny.storage.password'),
                ],
            ]);
        } catch (ClientException $exception) {
            throw FilesystemException::fromResponse($exception->getResponse());
        } catch (GuzzleException $exception) {
            throw FilesystemException::fromPrevious($exception);
        }

        if ($response->getStatusCode() === 404) {
            throw FileNotFoundException::fromFile($file);
        }

        if ($response->getStatusCode() !== 200) {
            throw FilesystemException::fromResponse($response);
        }

        return $response->getBody()->getContents();
    }

    public function put(LocalFile $file, string $local = '', string $edge = ''): PromiseInterface
    {
        return $this->client->putAsync(self::normalizePath($file->getFilename($local, $edge), $file->isDirectory()), [
            RequestOptions::HEADERS => [
                'AccessKey' => config('bunny.storage.password'),
                'Checksum' => $file->getChecksum(),
            ],
            RequestOptions::BODY => $file->getResource(),
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

    public function getStorageCache(): EdgeStorageCache
    {
        return $this->storageCache;
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

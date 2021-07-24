<?php

namespace App\Bunny\Filesystem;

use App\Bunny\Filesystem\Exceptions\FileNotFoundException;
use App\Bunny\Filesystem\Exceptions\FilesystemException;
use App\Bunny\Lock\Exceptions\LockException;
use App\Bunny\Lock\Lock;
use Psr\Http\Message\ResponseInterface;

class EdgeStorageCache
{
    private EdgeStorage $edgeStorage;
    private string $filename = Lock::DEFAULT_FILENAME;

    public function __construct(EdgeStorage $edgeStorage)
    {
        $this->edgeStorage = $edgeStorage;
    }

    /**
     * @throws FilesystemException
     * @throws LockException
     */
    public function parse(string $path): array
    {
        $contents = $this->edgeStorage->get(EdgeFile::fromFilename(
            sprintf('%s/%s', $path, $this->filename),
            File::EMPTY_SHA256,
        ));

        return $this->extract($contents);
    }

    public function save(string $local, string $edge, array $files): bool
    {
        $filename = sprintf('%s/%s', $edge, $this->filename);
        $contents = $this->hydrate($files, $local, $edge);
        $checksum = strtoupper(hash('sha256', $contents));

        $promise = $this->edgeStorage->put(new LocalFile($filename, $checksum, $contents));

        /** @var ResponseInterface $response */
        $response = $promise->wait();

        return $response->getStatusCode() === 200;
    }

    /**
     * @throws FileNotFoundException
     * @throws LockException
     */
    private function extract(string $contents): array
    {
        $lock = Lock::parse($contents, $this->filename);

        return array_map(fn(array $x) => EdgeFile::fromArray($x), $lock->getFiles());
    }

    private function hydrate(array $files, string $search = '', string $replace = ''): string
    {
        return Lock::fromFiles(
            array_map(fn(LocalFile $x) => $x->toArray($search, $replace), $files)
        )->toString();
    }

    public function setFilename(string $filename)
    {
        $this->filename = $filename;
    }
}

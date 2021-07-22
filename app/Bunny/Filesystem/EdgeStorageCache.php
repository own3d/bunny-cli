<?php

namespace App\Bunny\Filesystem;

use App\Bunny\Filesystem\Exceptions\FileNotFoundException;
use App\Bunny\Filesystem\Exceptions\FilesystemException;
use Psr\Http\Message\ResponseInterface;

class EdgeStorageCache
{
    private EdgeStorage $edgeStorage;
    private string $filename = '.well-known/bunny.sha256';

    public function __construct(EdgeStorage $edgeStorage)
    {
        $this->edgeStorage = $edgeStorage;
    }

    /**
     * @throws FilesystemException
     */
    public function parse(string $path): array
    {
        $contents = $this->edgeStorage->get(EdgeFile::fromFilename(
            sprintf('%s/%s', $path, $this->filename),
            File::EMPTY_SHA256,
        ));

        file_put_contents(base_path(sprintf('%s.bk', basename($this->filename))), $contents);

        return $this->extract($contents);
    }

    public function save(string $local, string $edge, array $files): bool
    {
        $filename = sprintf('%s/%s', $edge, $this->filename);
        $contents = $this->hydrate($files, $local, $edge);
        $checksum = strtoupper(hash('sha256', $contents));

        file_put_contents(base_path(sprintf('%s', basename($this->filename))), $contents);

        $promise = $this->edgeStorage->put(new LocalFile($filename, $checksum, $contents));

        /** @var ResponseInterface $response */
        $response = $promise->wait();

        return $response->getStatusCode() === 200;
    }

    private function extract(string $contents): array
    {
        if (!$array = json_decode($contents, true)) {
            throw new FileNotFoundException('Cannot parse cache file.');
        }

        return array_map(fn(array $x) => EdgeFile::fromArray($x), $array);
    }

    private function hydrate(array $files, string $search = '', string $replace = ''): string
    {
        return json_encode(array_map(fn(LocalFile $x) => $x->toArray($search, $replace), $files), JSON_PRETTY_PRINT);
    }

    public function setFilename(string $filename)
    {
        $this->filename = $filename;
    }
}

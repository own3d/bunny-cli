<?php

namespace App\Bunny\Filesystem;

use Illuminate\Support\Str;

class LocalFile implements File, FileStreamable, FileSerialized
{
    private string $filename;
    private ?string $checksum;
    private ?string $contents;

    public function __construct(string $filename, ?string $checksum, string $contents = null)
    {
        $this->filename = $filename;
        $this->checksum = $checksum;
        $this->contents = $contents;
    }

    public function getFilename($search = '', $replace = ''): string
    {
        return Str::replaceFirst($search, $replace, $this->filename);
    }

    public function getChecksum(): ?string
    {
        return $this->checksum;
    }

    public function isDirectory(): bool
    {
        return $this->getChecksum() === null;
    }

    public function getResource()
    {
        if ($this->contents) {
            return $this->contents;
        }

        return fopen($this->getFilename(), 'r');
    }

    public function toArray(string $search = '', string $replace = ''): array
    {
        return [
            'sha256' => $this->getChecksum(),
            'filename' => $this->getFilename($search, $replace),
        ];
    }

    public static function fromArray(array $array): self
    {
        return new self($array['filename'], $array['sha256']);
    }
}

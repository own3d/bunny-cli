<?php

namespace App\Bunny\Filesystem;

use Illuminate\Support\Str;
use stdClass;

class EdgeFile implements File, FileSerialized
{
    private stdClass $file;

    public function __construct(stdClass $file)
    {
        $this->file = $file;
    }

    public static function fromFilename(string $path, string $sha256 = null): self
    {
        return new self((object)[
            'Path' => Str::replaceLast($basename = basename($path), '', $path),
            'ObjectName' => $basename,
            'IsDirectory' => Str::endsWith($path, '/'),
            'Checksum' => $sha256,
        ]);
    }

    public function getFilename($search = '', $replace = ''): string
    {
        return Str::replaceFirst($search, $replace, $this->file->Path . $this->file->ObjectName);
    }

    public function isDirectory(): bool
    {
        return $this->getChecksum() === null;
    }

    public function getChecksum(): ?string
    {
        return $this->file->IsDirectory ? null : $this->file->Checksum;
    }

    public function toArray(): array
    {
        return [
            'sha256' => $this->getChecksum(),
            'filename' => $this->getFilename(),
        ];
    }

    public static function fromArray(array $array): self
    {
        return self::fromFilename($array['filename'], $array['sha256']);
    }
}

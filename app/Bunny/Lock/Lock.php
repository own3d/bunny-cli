<?php

namespace App\Bunny\Lock;

use App\Bunny\Filesystem\Exceptions\FileNotFoundException;
use App\Bunny\Lock\Exceptions\LockException;

class Lock
{
    public const DEFAULT_FILENAME = '.well-known/bunny-cli.lock';

    private array $contents;

    private function __construct(array $contents)
    {
        $this->contents['version'] = 1;
        $this->contents['_readme'] = [
            'This file locks the files of your project to a known state',
            'Read more about it at https://github.com/own3d/bunny-cli/wiki',
            'This file is @generated automatically'
        ];
        $this->contents['files'] = $contents['files'] ?? [];
    }

    public static function parse(string $contents, string $filename = self::DEFAULT_FILENAME): self
    {
        if (!$array = json_decode($contents, true)) {
            throw new FileNotFoundException(sprintf('Cannot decode %s file.', $filename));
        }

        if (!isset($array['version']) || $array['version'] !== 1) {
            throw LockException::fromInvalidVersion($array['version'] ?? 'undefined');
        }

        return new self($array);
    }

    public static function fromFiles(array $files): self
    {
        return new self(['files' => $files]);
    }

    public function getFiles(): array
    {
        return $this->contents['files'];
    }

    public function toArray(): array
    {
        return $this->contents;
    }

    public function toString(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }
}

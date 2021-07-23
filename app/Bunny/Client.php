<?php

namespace App\Bunny;

use GuzzleHttp\RequestOptions;

class Client
{
    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => 'https://api.bunny.net/',
        ]);
    }

    public function getPullZone(int $pullZoneId): Result
    {
        return $this->request('GET', "pullzone/{$pullZoneId}", [
            RequestOptions::HEADERS => [
                'AccessKey' => config('bunny.api.access_key'),
            ],
        ]);
    }

    public function purgeCache(int $pullZoneId): Result
    {
        return $this->request('POST', "pullzone/{$pullZoneId}/purgeCache", [
            RequestOptions::HEADERS => [
                'AccessKey' => config('bunny.api.access_key'),
            ],
        ]);
    }

    private function request(string $method, string $uri, array $options): Result
    {
        $response = $this->client->request($method, $uri, $options);

        return new Result($response);
    }

    public function getStorageZones(): Result
    {
        return $this->request('GET', 'storagezone', [
            RequestOptions::HEADERS => [
                'AccessKey' => config('bunny.api.access_key'),
            ],
        ]);
    }
}

<?php

return [
    'storage' => [
        'hostname' => env('BUNNY_STORAGE_HOSTNAME', 'storage.bunnycdn.com'),
        'username' => env('BUNNY_STORAGE_USERNAME'),
        'password' => env('BUNNY_STORAGE_PASSWORD'),
    ],
    'pull_zone' => [
        'id' => env('BUNNY_PULL_ZONE_ID'),
    ],
    'api' => [
        'access_key' => env('BUNNY_API_ACCESS_KEY'),
    ],
];

<?php

return [
    'default' => 'local',
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => bunny_cli_path(),
        ],
    ],
];

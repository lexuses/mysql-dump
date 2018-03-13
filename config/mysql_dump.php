<?php

return [
    'mysqldump' => '',

    'dir_name' => 'Y-m-d',
    'separator' => '/',
    'dump_name' => 'Y-m-d_H-i-s',
    'compress' => true,

    'storage' => [
        'local' => [
            'active' => true,
            'disk' => 'database',
            'path' => 'seeds/dumps',
        ],
        's3' => [
            'active' => true,
            'disk' => 's3',
            'path' => 'dumps'
        ],
    ],

    'max_dumps' => [

        'day' => 1,
        'week' => 0,
        'month' => 0,
        'year' => 0,

    ]

];
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | mysqldump command
    |--------------------------------------------------------------------------
    |
    | This value used to create dump with mysqldump program.
    | Check all features in official site: https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html
    |
    */
    'mysqldump' => 'mysqldump --complete-insert',

    /*
    |--------------------------------------------------------------------------
    | Separator
    |--------------------------------------------------------------------------
    |
    | Separator type for path.
    |
    */
    'separator' => '/',

    /*
    |--------------------------------------------------------------------------
    | Directory name for database dump
    |--------------------------------------------------------------------------
    |
    | Directory used for separate dumps by period. It wraps by date function.
    | Check docs: http://php.net/manual/ru/function.date.php
    |
    */
    'dir_name' => 'Y-m-d',

    /*
    |--------------------------------------------------------------------------
    | Database dump name
    |--------------------------------------------------------------------------
    |
    | Name of the database dump. It wraps by date function.
    | Check docs: http://php.net/manual/ru/function.date.php
    |
    */
    'dump_name' => 'Y-m-d_H-i-s',

    /*
    |--------------------------------------------------------------------------
    | Compress
    |--------------------------------------------------------------------------
    |
    | If true database dump would creating with gzip command.
    |
    */
    'compress' => true,

    /*
    |--------------------------------------------------------------------------
    | Temp path
    |--------------------------------------------------------------------------
    |
    | Temporary path used for save database dump before upload to cloud. It would deleted after that.
    |
    */
    'tmp_path' => storage_path('tmp'),

    /*
    |--------------------------------------------------------------------------
    | Storage drivers
    |--------------------------------------------------------------------------
    |
    | Each storage contains:
    | - active: make database dump or not
    | - disk: property from "Filesystem Disks". Check it in filesystems.php
    | - path: path to database dump directory
    |
    */
    'storage' => [
        'local' => [
            'active' => true,
            'disk' => 'database',
            'path' => 'seeds/dumps',
        ],
        's3' => [
            'active' => false,
            'disk' => 's3',
            'path' => 'dumps'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Max dumps
    |--------------------------------------------------------------------------
    |
    | Max count of database dump for period.
    |
    */
    'max_dumps' => [

        'day' => 1,
        'weekOfMonth' => 0,
        'month' => 0,
        'year' => 0,
        'total' => 0,
    ]

];
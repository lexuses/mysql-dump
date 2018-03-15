# mysql-dump
Laravel package for auto dump mysql database to local storage and auto upload to cloud like S3.

## Install

```
composer require lexuses/mysql-dump
```

For laravel below 5.5: add ```Lexuses\MysqlDump\MysqlDumpServiceProvider``` to your app.php 

Run this in the command line:
```
php artisan vendor:publish
```

Edit ```config/filesystems.php``` and set local storage or use existing:
```php
'disks' => [
    'database' => [
        'driver' => 'local',
        'root' => database_path(),
    ],
],
```

Edit ```config/mysql-dump.php``` and set where to storage, dump name, folders name, max dumps for period.

Set ```mysqldump``` command. By default it use ```--complete-insert``` flag it means save only data not structure. Check official docs [dev.mysql.com/doc/refman/5.7/en/mysqldump.html](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html)

You can use it with docker. So you have to set mysql dump to:
```php
    'mysqldump' => 'docker exec laradock_mysql_1 /usr/bin/mysqldump'
```
Don't forget to check name of you container.

## Usage
Auto mode. Add command to ```Console/Kernel.php```

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('mysql-dump:auto')->hourly();
}
```

Manual mode:
```
php artisan mysql-dump:export --storage=s3
```

List of dumps:
```
php artisan mysql-dump:list --storage=local
```
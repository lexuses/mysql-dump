<?php

namespace Lexuses\MysqlDump;

use Illuminate\Support\ServiceProvider;
use Lexuses\MysqlDump\Commands\MysqlDumpCommand;

class MysqlDumpServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MysqlDumpCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/mysql_dump.php' => config_path('mysql_dump.php')
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/mysql_dump.php', 'mysql_dump'
        );
    }
}
<?php

namespace Lexuses\MysqlDump;

use Illuminate\Support\ServiceProvider;
use Lexuses\MysqlDump\Commands\AutoCommand;
use Lexuses\MysqlDump\Commands\ExportCommand;
use Lexuses\MysqlDump\Commands\ListCommand;

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
                AutoCommand::class,
                ExportCommand::class,
                ListCommand::class,
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
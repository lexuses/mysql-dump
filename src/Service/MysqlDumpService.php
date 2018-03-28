<?php

namespace Lexuses\MysqlDump\Service;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class MysqlDumpService
{
    private $app;

    public function __construct(MysqlDumpApp $app)
    {
        $this->app = $app;
    }

    /**
     * @return MysqlDumpApp
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Running service in auto mode
     * It will create dumps by configuration file
     */
    public function auto()
    {
        $this->app->makeTemp();

        $this
            ->scanStorages()
            ->each(function($storageConfig, $storageName){

                $storage = $this->app->makeStorageWithTemp($storageName);
                $storage->makeDump();
                $storage->checkMaxDumps();
            });

        $this->app->clearTmp();
    }

    /**
     * Run service in manual mode
     * It will create dump by storage you chosen
     * @param string $storageName
     * @throws \Exception
     */
    public function dumpTo(string $storageName)
    {
        $this->app->makeTemp();

        $storage = $this->app->makeStorageWithTemp($storageName);
        $storage->makeDump();
    }

    /**
     * Scan for active storage in config file
     * @return Collection
     */
    protected function scanStorages()
    {
        return $this->getStorages()
            ->filter(function($storage){
                return isset($storage['active']) && $storage['active'] === true;
            });
    }

    public function getStorages($storage = null)
    {
        if(!$storage)
            return new Collection(Config::get('mysql_dump.storage'));

        return Config::get('mysql_dump.storage.' . $storage);
    }

    public function getAllTables()
    {
        $tables_from_db = DB::select('SHOW TABLES');
        $tables = [];
        foreach ($tables_from_db as $table)
        {
            $key = key(get_object_vars($table));
            $tables[] = $table->{$key};
        }

        return $tables;
    }

    public function truncate()
    {
        $tables = $this->getAllTables();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        foreach ($tables as $table)
        {
            //if($table == 'migrations')
            //    continue;

            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function drop()
    {
        $tables = $this->getAllTables();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        foreach ($tables as $table)
        {
            DB::statement('DROP TABLE IF EXISTS '.$table);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

}
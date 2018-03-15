<?php

namespace Lexuses\MysqlDump\Service;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class MysqlDumpService
{
    private $create;

    public function __construct(MysqlDumpCreate $create)
    {
        $this->create = $create;
    }

    /**
     * Running service in auto mode
     * It will create dumps by configuration file
     */
    public function auto()
    {
        $this->create->makeTemp();

        $this
            ->scanStorages()
            ->each(function($storageConfig, $storageName){

                $storage = $this->create->makeStorageWithTemp($storageName);
                $storage->makeDump();
                $storage->checkMaxDumps();
            });

        $this->create->clearTmp();
    }

    /**
     * Run service in manual mode
     * It will create dump by storage you chosen
     * @param string $storageName
     * @throws \Exception
     */
    public function dumpTo(string $storageName)
    {
        $this->create->makeTemp();

        $storage = $this->create->makeStorageWithTemp($storageName);
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

}
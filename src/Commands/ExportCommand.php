<?php

namespace Lexuses\MysqlDump\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Lexuses\MysqlDump\Service\MysqlDumpService;

class ExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mysql-dump:export {--storage= : Storage name from config file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create dump and save it to specified storage. Limit turned off for this command.';
    /**
     * @var MysqlDumpService
     */
    private $service;

    /**
     * Create a new command instance.
     *
     * @param MysqlDumpService $service
     */
    public function __construct(MysqlDumpService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        $storage = $this->option('storage');

        if(!$storage){
            $storage = $this->choice('Choose storage:', array_keys(Config::get('mysql_dump.storage')));
        }

        if(!$this->service->getStorages($storage)){
            $storages = $this->service
                ->getStorages()
                ->keys()
                ->map(function($name) {
                    return ' - '.$name;
                })
                ->implode("\n");

            return $this->error('Specified storage does not exists. Existing storages:' . "\n" . $storages);
        }

        $this->service->dumpTo($storage);

        $this->info('Done!');
    }
}
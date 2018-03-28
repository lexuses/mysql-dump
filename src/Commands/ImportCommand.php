<?php

namespace Lexuses\MysqlDump\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Lexuses\MysqlDump\Service\MysqlDumpService;
use Lexuses\MysqlDump\Service\MysqlDumpStorage;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mysql-dump:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import dump file to database.';
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
        $this->call('mysql-dump:truncate');

        $storage = $this->choice('Choose storage', array_keys(Config::get('mysql_dump.storage')));

        $storage = new MysqlDumpStorage($storage);
        $list = $storage->getDumpList();

        $dumpName = $this->choice('Choose dump', $list->map->getName()->values()->all());

        $dump = $list->filter(function($file) use ($dumpName){
            return $file->getName() == $dumpName;
        })->first();

        $tempFolder = Config::get('mysql_dump.tmp_path');

        $bar = null;
        if(!$dump->isLocal()){
            $this->info('Download dump:');
            $bar = $this->output->createProgressBar($dump->getMeta('size'));
        }

        $path = $dump->download($tempFolder, $length = 100, function($handle) use ($bar){
            $bar->setProgress(ftell($handle));
        });

        if(!$dump->isLocal())
            $bar->finish();

        $this->info("\nImport to database");
        $this->service->getApp()->import($path);

        if(!$dump->isLocal()){
            $this->info("\nClear temp files");
            $this->service->getApp()->setPath($path)->clearTmp();
        }

        $this->info("\nDone!");
    }
}
<?php

namespace Lexuses\MysqlDump\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Lexuses\MysqlDump\Service\MysqlDumpModel;
use Lexuses\MysqlDump\Service\MysqlDumpService;
use Lexuses\MysqlDump\Service\MysqlDumpStorage;

class ListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mysql-dump:list {--storage= : Storage name from config file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Return list of dumps on specified storage.';
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

        $storage = new MysqlDumpStorage($storage);
        $list = $storage->getDumpList()
            ->groupBy(function($dump){
                return $dump->getTime()->year;
            })
            ->mapWithKeys(function($dumps, $year){
                return [$year => $dumps->groupBy(function($dump){
                    return $dump->getTime()->month;
                })->mapWithKeys(function($dumps, $month){
                    return [$month => $dumps->groupBy(function($dump){
                        return $dump->getTime()->day;
                    })];
                })];
            });

        $this->showList($list, 0, 0);
    }

    public function showList($array, $tabTimes, $periodIndex)
    {
        $periods =  ['year', 'month', 'day', 'list'];
        $period = $periods[$periodIndex];
        $periodIndex++;
        $tabText = str_repeat(' ', $tabTimes);

        foreach ($array as $key => $dump){
            if($dump instanceof MysqlDumpModel){
                $this->info(str_repeat(' ', $tabTimes). ($key+1) . ') ' . $dump->getName());
                continue;
            }

            if($period == 'month'){
                $title = Carbon::now()->month($key)->format('F');
            }
            else{
                $title = $key;
            }

            $this->line($tabText."-- $title --".$tabText);
            if($dump instanceof Collection){
                $this->showList($dump, $tabTimes+6, $periodIndex);
            }
        }
    }
}
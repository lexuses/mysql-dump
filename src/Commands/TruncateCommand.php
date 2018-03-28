<?php

namespace Lexuses\MysqlDump\Commands;

use Illuminate\Console\Command;
use Lexuses\MysqlDump\Service\MysqlDumpService;

class TruncateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mysql-dump:truncate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate tables in database';
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
     */
    public function handle()
    {
        $this->warn('WARNING!');
        $this->warn('Are you sure you want to clear database.');
        $this->warn('You will lose all of your data. It can\'t be undone!');
        $truncate = $this->confirm('Truncate database');
        if($truncate) {
            $this->service->truncate();
            $this->info('All tables was truncated');
        }
        else {
            $this->info('Truncate skipped');
        }
    }
}
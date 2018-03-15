<?php

namespace Lexuses\MysqlDump\Commands;

use Illuminate\Console\Command;
use Lexuses\MysqlDump\Service\MysqlDumpService;

class AutoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mysql-dump:auto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create dump and save it. Check config file for more information.';
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
        $this->service->auto();
    }
}
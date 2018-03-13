<?php

namespace Lexuses\MysqlDump\Service;

use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class MysqlDumpService
{
    protected $separator;
    protected $folder;
    protected $name;
    protected $extension;
    protected $path;
    protected $tmpFolder = 'tmp';
    protected $tmpFolderPath;
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * MysqlDumpService constructor.
     * @param Filesystem $filesystem
     * @throws \Exception
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->separator = Config::get('mysql_dump.separator');
        $this->folder = date(Config::get('mysql_dump.dir_name'));
        $this->extension = Config::get('mysql_dump.compress') ? '.sql.gz' : '.sql';
        $this->name = date(Config::get('mysql_dump.dump_name'));
        $this->path = $this->folder . $this->separator . $this->name . $this->extension;
        $this->tmpFolderPath = storage_path('tmp');
        $this->filesystem = $filesystem;
    }

    public function clearTmp()
    {
        $path = $this->tmpFolderPath .
                $this->separator .
                $this->name . $this->extension;

        try{
            unlink($path);
        } catch (\Exception $e){
            throw new \Exception("Can't delete tmp file by this path: $path");
        }
    }

    protected function getDriver($driverName)
    {
        return Config::get('mysql_dump.storage.' . $driverName);
    }

    public function makeTmp()
    {
        if(!$this->filesystem->isDirectory($this->tmpFolderPath))
            $this->filesystem->makeDirectory($this->tmpFolderPath, 0755, true);

        $this->commandDump($this->tmpFolderPath .
            $this->separator .
            $this->name .
            $this->extension);

        return $this;
    }

    public function commandDump($path)
    {
        $mysqldumpPath = Config::get('mysql_dump.mysqldump');

        $password = '';
        if ($p = Config::get('database.connections.mysql.password'))
            $password = ' -p' . $p;

        $zip = Config::get('mysql_dump.compress') ? ' | gzip' : '';

        $command = $mysqldumpPath.'mysqldump --complete-insert -u ' .
            Config::get('database.connections.mysql.username') .
            $password .
            ' -t ' .
            Config::get('database.connections.mysql.database') .
            $zip .
            ' > ' .
            $path;

        try{
            exec($command);
        } catch (\Exception $e){
            throw new \Exception('Mysqldump command return error.');
        }

        return $this;
    }

    public function auto()
    {
        $this
            ->makeTmp()
            ->scanDrivers()
            ->each(function($driver, $driverName){
                $this->makeDump($driverName);
                $this->maxDumps($driverName);
            });

        $this->clearTmp();
    }

    protected function scanDrivers()
    {
        $drivers = new Collection(Config::get('mysql_dump.storage'));
        return $drivers->filter(function($driver){
            return isset($driver['active']) && $driver['active'] === true;
        });
    }

    public function runDriver(string $driverName)
    {
        $this
            ->makeTmp()
            ->makeDump($driverName);
    }

    protected function makeDump($driverName)
    {
        $driver = $this->getDriver($driverName);
        $dumpName = $this->name . $this->extension;
        $tmpPath = $this->tmpFolderPath . $this->separator . $dumpName;

        $path =
            $driver['path'] .
            $this->separator .
            $this->folder;

        $system = Config::get('filesystems.disks.' . $driver['disk']);

        if(!$system)
            throw new \Exception('Disk not found in filesystems.php');

        Storage::disk($driver['disk'])->makeDirectory($path, 0755, true);

        if(isset($system['root']))
            return $this->copy(
                $tmpPath,
                implode($this->separator, [
                    $system['root'],
                    $path,
                    $dumpName
                ])
            );

        return $this->upload($driver['disk'], $tmpPath, $path, $dumpName);
    }

    protected function copy($tmpPath, $destinationPath)
    {
        $this->filesystem->copy($tmpPath, $destinationPath);
    }

    protected function upload($disk, $tmpPath, $destinationPath, $dumpName)
    {
        Storage::disk($disk)
            ->putFileAs($destinationPath, new File($tmpPath), $dumpName);
    }

    protected function maxDumps($driverName)
    {
        $periods = new Collection(Config::get('mysql_dump.max_dumps'));
        $periods->filter(function($value){
            return $value;
        })->each(function($value, $period) use ($driverName){
            $currentDumps = $this->countBy($driverName, $period);
            if($currentDumps->count() > $value){
                $this->deleteFirstNDumps($driverName, $currentDumps, $currentDumps->count() - $value);
            }
        });
    }

    public function countBy($driverName, $period)
    {
        $now = Carbon::now();

        if(!isset($now->$period)){
            throw new \Exception('Period does not exists. Please check Carbon docs: http://carbon.nesbot.com/docs/#api-getters');
        }

        return $this
            ->getDumpList($driverName)
            ->pipe(function($collection) use ($period){
                return $this->filterAndSortDumpList($collection, $period);
            });
    }

    protected function getDumpList($driverName)
    {
        $driver = $this->getDriver($driverName);
        $disk = $driver['disk'];

        $files = new Collection(
            Storage::disk($disk)->allFiles( $driver['path'] )
        );
        return $files
            ->mapWithKeys(function($path) use ($disk){

                $modified = Storage::disk($disk)->lastModified($path);
                return [ $path => $modified ];

            });
    }

    protected function filterAndSortDumpList(Collection $files, $period)
    {
        $now = Carbon::now();

        if(!isset($now->$period)){
            throw new \Exception('Period does not exists. Please check Carbon docs: http://carbon.nesbot.com/docs/#api-getters');
        }

        return $files
            ->filter(function($time, $path) use ($period, $now){

                return $this->isInPeriod($period, $time, $now);

            })
            ->sort();
    }

    protected function isInPeriod($period, $time, $now)
    {
        $time = Carbon::createFromTimestamp($time);
        $availablePeriods = [
            'second', 'minute', 'hour', 'day', 'month', 'year'
        ];

        $periodIndex = array_search($period, $availablePeriods);
        if($periodIndex === false){
            throw new \Exception('Period does not exists. Please check Carbon docs: http://carbon.nesbot.com/docs/#api-getters');
        }

        while(isset($availablePeriods[$periodIndex])){
            $period = $availablePeriods[$periodIndex];
            if($time->$period != $now->$period)
                return false;

            $periodIndex++;
        }

        return true;
    }

    protected function deleteFirstNDumps($driverName, $currentDumps, $n)
    {
        $driver = $this->getDriver($driverName);

        for($i=0; $i < $n; $i++){
            $path = $currentDumps->keys()->first();
            Storage::disk($driver['disk'])->delete($path);
            $currentDumps->forget($path);
        }

        return $currentDumps;
    }


}
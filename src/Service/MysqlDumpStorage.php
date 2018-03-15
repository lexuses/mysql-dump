<?php

namespace Lexuses\MysqlDump\Service;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class MysqlDumpStorage
{
    /**
     * @var MysqlDumpCreate
     */
    private $dump;

    protected $storageName;
    protected $storage;
    protected $separator;
    protected $system;
    protected $dumpDir;
    protected $path;

    public function __construct($storageName)
    {
        $this->storageName = $storageName;
        $this->storage = $this->getStorage($storageName);
        $this->system = Config::get('filesystems.disks.' . $this->storage['disk']);

        if(!$this->system)
            throw new \Exception('Disk not found in filesystems.php');

        $this->separator = Config::get('mysql_dump.separator');
        $this->dumpDir = date(Config::get('mysql_dump.dir_name'));
        $this->path =
            $this->storage['path'] .
            $this->separator .
            $this->dumpDir;
    }

    public function setCreator(MysqlDumpCreate $creator)
    {
        $this->dump = $creator;
    }

    /**
     * Return storage from config
     * @param $storageName
     * @return mixed
     */
    protected function getStorage($storageName)
    {
        return Config::get('mysql_dump.storage.' . $storageName);
    }

    /**
     * Return path to dump file
     * @param $dumpName
     * @return string
     */
    protected function getPathToDump($dumpName)
    {
        if(isset($this->system['root']))
            return implode($this->separator, [
                $this->system['root'],
                $this->path,
                $dumpName
            ]);

        return $this->path . $this->separator . $dumpName;
    }

    /**
     * Copy dump from temp to destination folder
     * @throws \Exception
     */
    public function makeDump()
    {
        $dumpName = $this->dump->getName();
        $model = new MysqlDumpModel(
            $this->storage['disk'],
            $this->getPathToDump($dumpName)
        );

        Storage::disk($this->storage['disk'])->makeDirectory($this->path, 0755, true);

        $function = $this->system['driver'] == 'local' ? 'copy' : 'upload';

        return $model->$function($this->dump->getPath());
    }

    /**
     * Return dump list of the storage
     * @return Collection
     */
    public function getDumpList()
    {
        $disk = $this->storage['disk'];

        $files = new Collection(
            Storage::disk($disk)->allFiles( $this->storage['path'] )
        );
        return $files
            ->map(function($path) use ($disk){
                return new MysqlDumpModel($disk, $path);
            })
            ->sortBy(function($model){
                return $model->getLastModified();
            });
    }


    public function checkMaxDumps()
    {
        $dumps = $this->getDumpList();

        $periods = new Collection(Config::get('mysql_dump.max_dumps'));
        $periods->filter(function($value){
            return $value;
        })->each(function($value, $period) use ($dumps){

            $filteredDumps = $this->countBy($dumps, $period);

            if($filteredDumps->count() > $value){
                $filteredDumps
                    ->take($filteredDumps->count() - $value)
                    ->each(function($dump, $key) use ($dumps){
                        /** @var MysqlDumpModel $dump */
                        $dump->delete();
                        $dumps->forget($key);
                    });
            }

        });
    }

    public function countBy(Collection $dumps, $period)
    {
        if($period == 'total')
            return $dumps;

        $now = Carbon::now();

        if(!isset($now->$period)){
            throw new \Exception('Period does not exists. Please check Carbon docs: http://carbon.nesbot.com/docs/#api-getters');
        }

        return $dumps
            ->filter(function($model) use ($period, $now){
                return $model->isInPeriod($period, $now);
            });
    }
}
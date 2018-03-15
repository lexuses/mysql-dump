<?php

namespace Lexuses\MysqlDump\Service;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;

class MysqlDumpModel
{
    protected $path;
    protected $name;
    protected $timestamp;
    protected $disk;

    /**
     * MysqlDumpModel constructor.
     * @param $disk
     * @param $path
     */
    public function __construct($disk, $path)
    {
        $this->path = $path;
        $this->name = basename($path);
        $this->disk = $disk;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLastModified()
    {
        $this->timestamp = Storage::disk($this->disk)->lastModified($this->path);
        return $this->timestamp;
    }

    public function getTime()
    {
        return Carbon::createFromTimestamp($this->timestamp);
    }

    public function copy($tmpPath)
    {
        try{
            copy($tmpPath, $this->path);
        } catch (\Exception $e){
            throw new \Exception('Error on copy tmp dump to destination folder');
        }

        return $this->path;
    }

    public function upload($tmpPath)
    {
        Storage::disk($this->disk)
            ->putFileAs(
                str_replace($this->name, '', $this->path),
                new File($tmpPath),
                $this->name
            );
    }

    public function delete()
    {
        Storage::disk($this->disk)->delete($this->path);
    }

    public function isInPeriod($period, $now)
    {
        if(!$this->timestamp)
            $this->getLastModified();

        $time = Carbon::createFromTimestamp($this->timestamp);

        $availablePeriods = [
            'second', 'minute', 'hour', 'day', 'weekOfMonth', 'month', 'year'
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
}
<?php

namespace Lexuses\MysqlDump\Service;

use Illuminate\Support\Facades\Config;
use Illuminate\Filesystem\Filesystem;

class MysqlDumpApp
{
    protected $tmpFolderPath;
    protected $extension;
    protected $name;
    protected $separator;
    /**
     * @var Filesystem
     */
    private $filesystem;
    protected $path;


    public function __construct(Filesystem $filesystem)
    {
        $this->tmpFolderPath = Config::get('mysql_dump.tmp_path');
        $this->separator = Config::get('mysql_dump.separator');
        $this->extension = Config::get('mysql_dump.compress') ? '.sql.gz' : '.sql';
        $this->name = date(Config::get('mysql_dump.dump_name')) . $this->extension;
        $this->path = $this->tmpFolderPath .
            $this->separator .
            $this->name .
            $this->extension;
        $this->filesystem = $filesystem;
    }

    /**
     * Get dump name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Get dump path
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    public function makeStorageWithTemp($storageName)
    {
        $storage = new MysqlDumpStorage($storageName);
        $storage->setCreator($this);
        return $storage;
    }

    public function makeTemp()
    {
        if(!$this->filesystem->isDirectory($this->tmpFolderPath))
            $this->filesystem->makeDirectory($this->tmpFolderPath, 0755, true);

        $this->commandDump($this->path);

        return $this;
    }

    public function commandDump($path)
    {
        $mysqldumpPath = Config::get('mysql_dump.mysqldump');

        $password = '';
        if ($p = Config::get('database.connections.mysql.password'))
            $password = ' -p' . $p;

        $zip = Config::get('mysql_dump.compress') ? ' | gzip' : '';

        $command = $mysqldumpPath .
            ' -u ' . Config::get('database.connections.mysql.username') .
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
    }

    public function import($path)
    {
        $password = '';
        if ($p = Config::get('database.connections.mysql.password'))
            $password = ' -p' . $p;

        $zip = '';
        if(Config::get('mysql_dump.compress')) {
            if(stripos(Config::get('mysql_dump.unzip_app'), '{file}') === false)
                throw new \Exception('Wrong unzip command');
            $zip = trim(str_replace('{file}', $path, Config::get('mysql_dump.unzip_app')) . ' ');
        }

        $after = ! Config::get('mysql_dump.compress') ? ' < ' . $path : '';

        $command = $zip .
            Config::get('mysql_dump.mysql_app') .
            ' -u ' . Config::get('database.connections.mysql.username') .
            $password .
            ' ' .
            Config::get('database.connections.mysql.database') .
            $after;

        try{
            exec($command);
        } catch (\Exception $e) {
            throw new \Exception('Import command return error.');
        }
    }

    public function clearTmp()
    {
        try{
            unlink($this->path);
        } catch (\Exception $e){
            throw new \Exception("Can't delete tmp file by this path: $this->path");
        }

        return $this;
    }
}
<?php

namespace App\Core;

use App\Core\DB;
use Exception;

class Model extends DB
{
    protected static $table;
    protected static $primary = "id";
    protected static $alias;
    protected static $map = [];
    protected static $hidden = [];
    protected static $uppers = []; // Uppercase fields
    protected static $files = []; // File fields
    protected static $required = [];
    protected static $mediaPath;
    protected static $data = [];

    public function __construct()
    {
        self::$data = [];
    }

    public function __set($name, $value)
    {
        static::$data[$name] = $value;
    }

    public function __get($name)
    {
        if (!empty(static::${$name})) {
            return static::${$name};
        }
        return null;
    }

    /**
     * Set the value of table
     *
     * @param string $table
     * @return self
     */
    public static function setTable($table)
    {
        static::$table = $table;
    }

    /**
     * Set the value of alias
     *
     * @param string $alias
     * @return string;
     */
    public static function setAlias($alias)
    {
        static::$alias = $alias;
    }

    /**
     * Set the value of map
     *
     * @param  array $map
     * @return string;
     */
    public static function setMap($map)
    {
        static::$map = $map;
    }

    /**
     * Set the value of hidden
     *
     * @param  array $hidden
     * @return array;
     */
    public static function setHidden($hidden)
    {
        static::$hidden = $hidden;
    }

    /**
     * Set the value of uppers
     *
     * @param  array $uppers
     * @return array
     */
    public static function setUppers($uppers)
    {
        static::$uppers = $uppers;
    }

    /**
     * Set the value of uppers
     *
     * @param  array $required
     * @return array
     */
    public static function setRequired($required)
    {
        static::$required = $required;
    }

    /**
     * Set the value of files
     *
     * @param  array $files
     * @return array
     */
    public static function setFiles($files)
    {
        static::$files = $files;
    }

    /**
     * Get the value of table
     *
     * @return string
     */
    public static function getTable()
    {
        return static::$table;
    }

    /**
     * Get the value of primary key
     *
     * @return string
     */
    public static function getPrimary()
    {
        return static::$primary;
    }

    /**
     * Get the value of map
     *
     * @return array
     */
    public static function getMap()
    {
        return static::$map;
    }

    /**
     * Get the value of hidden
     *
     * @return array
     */
    public static function getHidden()
    {
        return static::$hidden;
    }

    /**
     * Get the value of uppers
     *
     * @return array
     */
    public static function getUppers()
    {
        return static::$uppers;
    }

    /**
     * Get the value of files
     *
     * @return array
     */
    public static function getFiles()
    {
        return static::$files;
    }

    /**
     * Get the value of files
     *
     * @return array
     */
    public static function getRequired()
    {
        return static::$required;
    }

    /**
     * Get the value of media_path
     *
     * @return string
     */
    public static function getMediaPath()
    {
        return static::$mediaPath;
    }

    /**
     * Set the alias value of columns on query
     *
     * @return array
     */
    public static function mapping()
    {

        if (!empty(static::$hidden)) {
            foreach (static::$hidden as $h) {

                unset( static::$map[$h] );
            }
        }

        if (!empty(static::$map)) {

            foreach (static::$map as $column => $alias) {

                $maps[] = (static::$alias ? static::$alias."." : "")."{$column} AS {$alias}";
            }

            return $maps;
        }

        return;
    }

    protected static function toUpper($fields)
    {
        if (!empty(static::$uppers)) {

            (array)$fields;

            if (static::$uppers[0]=="*") {

                foreach ($fields as $key => $value) {

                    if (!empty($value) && is_string($value)) {

                        $fields[$key] = mb_strtoupper(trim($value), 'UTF-8');
                    }
                }

            } else {

                foreach (static::$uppers as $value) {

                    if (!empty($value) && is_string($value)) {

                        $fields[$value] = mb_strtoupper(trim($fields[$value]), 'UTF-8');
                    }
                }
            }
        }

        return $fields;
    }

    public static function findById($id)
    {

        $data = DB::table(static::$table.(static::$alias ? ' as '.static::$alias : ''))
            ->where((static::$alias ? static::$alias.'.' : '').(static::$primary ?? "id"), $id);

        if (static::$map) {
            $data = $data->select( self::mapping() );
        }

        $data = $data->get();

        static::$data = isset(((array)$data)[0]) ? ((array)$data)[0] : null;

        return static::$data;
    }

    public static function map()
    {

        // static::$table."<br>";
        // die("method map");

        $data = DB::table(static::$table.(static::$alias ? ' AS '.static::$alias : ''));

        if (static::$map) {
            $data = $data->select( self::mapping() );
        }

        $data = $data->get();

        return $data;
    }

    public static function firstOrCreate($fields, $verify=null)
    {

        if ($verify) {
            $matches = (array)$verify;
            foreach ($fields as $f => $v){
                if (!in_array($f, $matches)) {
                    unset($fields[$f]);
                }
            }
        }

        // Check if exist
        $data = DB::table(static::$table);
        foreach ($fields as $field => $value) {
            $data = $data->where($field, $value);
        }
        $data = $data->first();

        if (isset($data)) {

            return $data;

        } else {

            return self::create($fields);
        }
    }

    public static function create($fields)
    {
        $fields = self::toUpper($fields);
        $create = DB::table(static::$table)->insert($fields);
        return $create;
    }

    public static function update($value, $fields=null, $column=null, $operator=null)
    {

        $o = $operator ?? "=";
        $c = $column ?? static::$primary;

        $fields = self::toUpper($fields);

        $update = DB::table(static::$table)->where($c, $value, $o)->update($fields);

        return $update;
    }

    public static function delete($id=null, $column=null, $operator=null)
    {
        if (!$id) {

            if (empty(static::$data))
                throw new Exception("Data is empty");

            if (isset(static::$data[self::getPrimary()])) {

                $id = static::$data[self::getPrimary()];

                unset(static::$data[self::getPrimary()]);

                return self::delete($id);
            }

        } else {

            $delete = DB::table(static::$table)
                ->where(static::$primary, $id)
                ->delete();

            return $delete;
        }
    }

    public static function getOrder()
    {
        $order = DB::table(static::$table)->max('ordem');
        return $order+1;
    }

    public static function save()
    {

        if (empty(static::$data))
        throw new Exception("Data is empty");

        static::$data = self::toUpper((array)static::$data);

        dd(static::$data);

        if (isset(static::$data[self::getPrimary()])) {

            $id = static::$data[self::getPrimary()];

            unset(static::$data[self::getPrimary()]);

            return parent::where(self::getPrimary(), $id)->update(static::$data);
        }
        else {

            $insert = parent::insert(static::$data);

            return $insert;
        }
    }

}

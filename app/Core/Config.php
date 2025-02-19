<?php

namespace App\Core;

use Exception;

class Config
{

    private static $base;

    public function __construct()
    {
        self::$base = __DIR__."/../../config";
    }

    public static function get($config)
    {

        new Config();

        if (!self::$base) {

            throw new Exception("PATH_CONFIG not configured");
        }

        $index = explode(".", $config);

        if (file_exists(self::$base . '/' . $index[0] . '.php')) {

            $array = require self::$base . '/' . $index[0] . '.php';

            $nodes = count($index);

            for ($i=1; $i<$nodes; $i++)
            {
                $array = $array[ $index[$i] ];
            }

            return $array;

        } else {

            return 'Config file not found';
        }

    }

}

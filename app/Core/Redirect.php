<?php

namespace App\Core;

class Redirect
{

    public static function go($url)
    {
        return header("Location: ".$url);
        exit;
    }

    public static function referer()
    {
        if ($_SERVER["HTTP_REFERER"])
            $url = $_SERVER["HTTP_REFERER"];
        else
            $url = URL_BASE; //self::protocol().$_SERVER["HTTP_HOST"];

        self::go($url);
    }

    public static function previous()
    {
        self::referer();
    }

    private static function protocol()
    {

        if(strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === false)
        {
            $protocol = 'http://'; //Atribui o valor http
        }
        else
        {
            $protocol = 'https://'; //Atribui o valor https
        }

        return $protocol;
    }

    public static function uri()
    {

        return self::protocol().$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
    }

}

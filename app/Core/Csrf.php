<?php
namespace App\Core;

use App\Core\Session;
use Exception;

class Csrf
{

    /** @var Session */
    public static $session;

    /** @var Config */
    private static $config;

    public function __construct()
    {
        self::$session = new Session();

        self::$config = Config::get("csrf");
    }

    public static function generate()
    {
        new Csrf;

        if (self::$config["secure"]) {

            $hash = hash_hmac(self::$config["algo"], self::$config["data"], self::$config["key"], self::$config["output"]);
            self::$session->set(self::$config["hash_name"], $hash);

        } else {

            $hash = null;
        }

        return $hash;
    }

    public static function confirm($token): bool
    {
        new Csrf;

        if (self::$session->has(self::$config["hash_name"])) {

            return hash_equals(self::$session->{self::$config["hash_name"]}, $token);

        } else {

            return false;
        }
    }

}

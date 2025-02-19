<?php
namespace App\Core;

use \App\Core\Config;

class Password
{

    /** @var Config */
    private static $config;

    public function __construct()
    {
        self::$config = Config::get("passwd");


    }

    public static function hash(string $password): string
    {
        new Password();
        return password_hash($password, self::$config["algo"], self::$config["option"]);
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function rehash(string $hash): bool
    {
        new Password();
        return password_needs_rehash($hash, self::$config["algo"], self::$config["option"]);
    }

    public static function generate($length, $upper=null, $lower=null, $number=null, $symbol=null)
    {
        if (!$upper)  $upper = "ABCDEFGHIJKLMNOPQRSTUVYXWZ"; // contem as letras maiúsculas
        if (!$lower)  $lower = "abcdefghijklmnopqrstuvyxwz"; // contem as letras minusculas
        if (!$number) $number= "0123456789"; // contem os números
        if (!$symbol) $symbol= "!@#$%&*"; // contem os símbolos

        $pass = str_shuffle($upper).str_shuffle($lower).str_shuffle($number).str_shuffle($symbol);

        $password = substr(str_shuffle($pass),0,$length);

        return [$password, self::hash($password)];
    }

    public static function encrypt($value, $sep="$")
    {
        $chaves = ['acefwhjlmnpqstuwxy123456789','acdfhiklmnvprtuvwxz123456789'];
        $chave = $chaves[ array_rand($chaves) ];
        $value64 = rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $pass = substr(str_shuffle($chave),0,rand(1,strlen($chave))).
                $sep.
                strrev(substr($value64,ceil(strlen($value64)/4)*3,ceil(strlen($value64)/4))).
                $sep.
                substr(str_shuffle($chave),0,rand(1,strlen($chave))).
                $sep.
                strrev(substr($value64,ceil(strlen($value64)/4)*2,ceil(strlen($value64)/4))).
                $sep.
                substr(str_shuffle($chave),0,rand(1,strlen($chave))).
                $sep.
                strrev(substr($value64,ceil(strlen($value64)/4)*1,ceil(strlen($value64)/4))).
                $sep.
                substr(str_shuffle($chave),0,rand(1,strlen($chave))).
                $sep.
                strrev(substr($value64,ceil(strlen($value64)/4)*0,ceil(strlen($value64)/4))).
                $sep.
                substr(str_shuffle($chave),0,rand(1,strlen($chave)));
        return $pass;
    }

    public static function decrypt($value, $sep="$")
    {
        $parts  = explode($sep, $value);
        $base64 = strrev($parts[7]).strrev($parts[5]).strrev($parts[3]).strrev($parts[1]);
        $pass   = base64_decode(str_pad(strtr($base64, '-_', '+/'),strlen($base64) % 4, '=', STR_PAD_RIGHT));
        return $pass;
    }

}

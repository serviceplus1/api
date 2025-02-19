<?php
namespace App\Core;

use Exception;

class Jwt
{

    private static $config;
    private static $key;
    private static $header;
    private static $payload;

    public function __construct()
    {

        self::$config = Config::get("jwt");

        self::$key = self::$config["public_key"];

        self::$header["typ"] = "JWT";

        self::$header["alg"] = self::$config["alg"];
    }

    public static function generate($claims=null)
    {
        new Jwt();

        $now = time();

        if (self::$config["audience"])
            self::$payload["aud"] = self::$config["audience"];

        if (self::$config["subject"])
            self::$payload["sub"] = self::$config["subject"];

        if (self::$config["not_before"])
            self::$payload["nbf"] = $now + self::$config["not_before"];

        if (self::$config["expires_at"])
            self::$payload["exp"] = $now + self::$config["expires_at"];

        self::$payload["iat"] = $now;
        self::$payload["iss"] = self::url_origin();
        self::$payload["jti"] = substr(sha1($now), 0, 8);

        if ($claims) {

            $claims = (array)$claims;

            foreach ($claims as $claim => $value) {

                self::$payload[$claim] = $value;
            }
        }

        $token = \Firebase\JWT\JWT::encode(self::$payload, self::$key, self::$config["alg"]);

        return $token;
    }

    public static function decode($token)
    {
        new Jwt();
        $decoded = \Firebase\JWT\JWT::decode($token, self::$key, array(self::$config["alg"]));
        return $decoded;
    }

    public static function validate($token)
    {

        try {

            self::decode($token);
            return true;

        } catch (Exception $e) {

            return false;
        }
    }

    private static function url_origin()
    {
        $s        = $_SERVER;
        $ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
        $sp       = strtolower( $s['SERVER_PROTOCOL'] );
        $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
        return $protocol . '://' . $s['SERVER_NAME'] . $s['REQUEST_URI'];
        // return $protocol . '://' . $s['SERVER_NAME'];
    }


}

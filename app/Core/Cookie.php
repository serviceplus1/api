<?php
namespace App\Core;

use \App\Core\Config;

class Cookie
{

    protected $time;
    protected $path;

    public function __construct()
    {

        $this->time = Config::get("session.cookie.days");
        $this->path = Config::get("session.cookie.path");
    }

    public function __get($name)
    {
        if (!empty($_COOKIE)) {
            return $_COOKIE;
        }
        return null;
    }

    public function __isset($name)
    {
        $this->has($name);
    }

    public function all(): ?object
    {
        return (object)$_COOKIE;
    }

    public function set(string $name, $value): Cookie
    {
        setcookie($name, $value, time()+60*60*24*$this->time, $this->path);
        return $this;
    }

    public function unset(string $name): Cookie
    {
        setcookie($name, '', time() - 3600, $this->path);
        return $this;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

}

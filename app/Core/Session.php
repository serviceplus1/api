<?php
namespace App\Core;

use \App\Core\Config;

class Session
{

    /** @var Config */
    private $config;

    public function __construct()
    {
        $this->config = Config::get("session");

        if (!session_id()){
            if ($this->config["driver"]=="file" && $this->config["path"]) {
                session_save_path($this->config["path"]);
            }
            session_start();
        }
    }

    public function __get($name)
    {
        if (!empty($_SESSION[$name])) {
            return $_SESSION[$name];
        }
        return null;
    }

    public function __isset($name)
    {
        $this->has($name);
    }

    public function all(): ?object
    {
        return (object)$_SESSION;
    }

    public function set(string $key, $value): Session
    {
        $_SESSION[$key] = is_array($value) ? (object)$value : $value;
        return $this;
    }

    public function unset(string $key): Session
    {
        unset($_SESSION[$key]);
        return $this;
    }

    public function del(string $key)
    {
        $this->unset($key);
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function regenerate(): Session
    {
        session_regenerate_id(true);
        return $this;
    }

    public function destroy(): Session
    {
        session_destroy();
        return $this;
    }

}

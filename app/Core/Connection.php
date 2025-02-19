<?php
/**
 * Classe de Conexão com o Banco de Dados
 */

namespace App\Core;

use PDO;
use PDOException;
use App\Core\Config;
use Exception;

class Connection
{
    private static $conn;

    /** @var Config */
    private static $config;

    public function __construct()
    {

    }

    private static function setConfig($type)
    {
        if ($type=="tenant") {

            self::$config = Config::get("tenant");
        }
        else {

            $driver = Config::get("database.default");
            self::$config = Config::get("database.connections.".$driver);
        }
    }

    /**
     * @return PDO
     */
    protected static function connect($type=null)
    {
        new Connection;

        self::setConfig($type);

        try {

            if (!isset(self::$conn)) {

                self::$conn = new PDO(
                    self::$config["driver"] . ':host=' . self::$config["host"] . ';'
                    .(self::$config["port"] ? 'port='.self::$config["port"].';' : '')
                    .self::$config["charset"].'dbname='
                    . self::$config["database"], self::$config["username"], self::$config["password"]
                );
                self::$conn->setAttribute(
                    PDO::ATTR_ERRMODE,
                    PDO::ERRMODE_EXCEPTION
                );
                self::$conn->setAttribute(
                    PDO::ATTR_DEFAULT_FETCH_MODE,
                    PDO::FETCH_OBJ
                );

            }

        } catch (PDOException $e) {

            die("PDO error: " . $e->getMessage());

        } catch (Exception $e) {

            die("Exception error: " . $e->getMessage());
        }

        return self::$conn;

    }
}

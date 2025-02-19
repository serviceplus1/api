<?php

namespace App\Core;

use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

class Log
{
    private $file;

    /** @var Config */
    private $config;

    private $path;

    public function __construct()
    {

        $this->config = Config::get("log");

        $this->path = $this->config["path"];
    }

    public function app(string $message, array $data = [], bool $extra = true): void
    {
        $messages = [
            'insert' => "INSERT", //'Um novo registro foi inserido',
            'update' => "UPDATE", //'Um registro foi alterado',
            'delete' => "DELETE", //'Um registro foi excluído',
            'delete_file' => "DELETE FILE", //'Um registro foi excluído',
            'status' => "STATUS", //'Um resgitro foi alterado o status',
            'login'  => "LOGIN" , //'Entrada no sistema',
            'logout' => "LOGOUT", //'Saída do sistema',
            'deletecheck' => "DELETECHECK", //'Vários registros foram excluídos',
            'statuscheck' => "STATUSCHECK", //'Vários registros tiveram o status alterado',
            'pass' => "UPDATE PASS", //'Atualizou a sua senha',
        ];

        $text = array_key_exists($message, $messages) ? $messages[$message] : $message;

        $this->create("app", "info", $text, "app", $data, $extra);
    }

    public function erro(string $message, array $data = [], bool $extra = true): void
    {
        $this->create("erro", "error", $message, "error", $data, $extra);
    }

    public function info(string $message, array $data = [], bool $extra = true): void
    {
        $this->create("info", "info", $message, "info", $data, $extra);
    }

    public function exception($exception)
    {

        $logger = new Logger("error");

        $this->file = $this->path."/error/".date("Y-m-d").".log";

        $logger->pushHandler(new StreamHandler($this->file, Logger::ERROR));

        $logger->pushProcessor(function ($record) {

            // $record["extra"]["HTTP_HOST"] = $_SERVER["HTTP_HOST"];
            $record["extra"]["URI"] = $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
            $record["extra"]["REQUEST_METHOD"] = $_SERVER["REQUEST_METHOD"];
            return $record;
        });

        $message = sprintf('Uncaught Exception %s: "%s" at %s line %s', get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine());

        $logger->error($message);
    }

    private function create(string $name, string $type, string $message, string $subpath=null, array $data, bool $extra)
    {

        $logger = new Logger($name);

        $this->file = $this->path."/".($subpath?$subpath."/":"").date("Y-m-d"). "." .$this->config["extension"];

        if ($type=="info")
            $logger->pushHandler(new StreamHandler($this->file, Logger::INFO));
        elseif ($type=="error")
            $logger->pushHandler(new StreamHandler($this->file, Logger::ERROR));
        elseif ($type=="debug")
            $logger->pushHandler(new StreamHandler($this->file, Logger::DEBUG));

        if ($extra) {

            $logger->pushProcessor(function ($record) {

                // $record["extra"]["HTTP_HOST"] = $_SERVER["HTTP_HOST"];
                $record["extra"]["URI"] = $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
                $record["extra"]["REQUEST_METHOD"] = $_SERVER["REQUEST_METHOD"];
                return $record;
            });
        }

        if ($type=="info")
            $logger->info($message, $data);
        elseif ($type=="error")
            $logger->error($message, $data);
        elseif ($type=="debug")
            $logger->debug($message, $data);


    }



}

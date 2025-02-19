<?php
namespace App\Core;

use App\Core\Response as Response;
use App\Core\Message;
use App\Core\Password;
use App\Core\Csrf;
use App\Core\Redirect;
use Exception;
use Respect\Validation\Validator as v;

class Data
{

    private $data;
    private $response;
    private $errCode;

    public function __construct($data=null, $ignoreNull=true, $ignoreStrips=[])
    {

        if ($data) {

            $data = (array)$data;

            $valid = $this->filter($data, $ignoreNull, $ignoreStrips);

            $this->data = $valid;

            if (!$ignoreNull) {
                $this->nullIfEmpty("*");
            }

            $this->csrf();
        }

        $this->errCode = 400;
        $this->response = "json";
    }

    public function setData($data, $ignoreNull=true)
    {
        $data = (array)$data;
        $valid = $this->filter($data, $ignoreNull);
        $this->data = $valid;
        return $this;
    }

    /**
     * json, exception, message (default)
     */
    public function setResponse($type)
    {
        $this->response = $type;
        return $this;
    }

    public function setErrCode($errCode)
    {
        $this->errCode = $errCode;
        return $this;
    }

    public function toObject()
    {
        (object)$this->data;
        return $this;
    }

    public function __get($name)
    {
        if (!empty($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
    }

    public function json($ignoreNull=true)
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $this->setData($data, $ignoreNull);
        return $this;
    }

    public function all()
    {
        return $this->data;
    }

    public function has(string $name): bool
    {
        if (!isset($this->data[$name]) || empty($this->data[$name]) || is_null($this->data[$name])) {
            return false;
        }
        return true;
    }

    /**
     * Checa a existência da chave no array, mesmo estando vazia
     */
    public function contain(string $name): bool
    {
        if (!array_key_exists($name, $this->data)) {
            return false;
        }
        return true;
    }

    public function add($field, $value): Data
    {

        $this->data[$field] = $value;
        return $this;
    }

    public function remove($fields): Data
    {

        $fields = (array)$fields;

        foreach ($fields as $f) {
            unset($this->data[$f]);
        }

        return $this;
    }

    public function del($fields): Data
    {
        return $this->remove($fields);
    }

    private function filter($data, $ignoreNull=true, $ignoreStrips=[])
    {

        if (!empty($ignoreStrips)) {

            foreach($ignoreStrips as $field) {

                $strips[$field] = $data[$field];
                unset($data[$field]);
            }

            $data1 = filter_var_array($data, FILTER_SANITIZE_STRIPPED);

            // Filter Stripeds
            $data2 = filter_var_array($strips);

            $data = array_merge($data1, $data2);
        }
        else {

            $data = filter_var_array($data, FILTER_SANITIZE_STRIPPED);
        }

        if ($ignoreNull) {

            $data = array_filter(
                $data, function ($v) {
                    return $v !== false && !is_null($v) && ($v != '' || $v == '0');
                }
            );
        }

        return $data;
    }

    public function ignore($fields)
    {

        $fields = (array)$fields;

        foreach ($fields as $f) {

            if (is_null($this->data[$f]) || empty($this->data[$f]) || $this->data[$f] === '') {

                $ignores[] = $f;
            }
        }

        if (isset($ignores))
            $this->remove($ignores);

        return $this;
    }

    /* Campos Somente */
    public function only($fields)
    {

        $fields = (array)$fields;

        foreach ($fields as $f) {

            $new[$f] = $this->data[$f];
        }

        $this->data = $new;

        return $this->data;
    }

    /**
     * required function
     * Obriga que todos os campos da lista passada sejam obrigatórios
     *
     * @param array $fields
     * @return void
     */
    public function required($fields)
    {

        $fields = (array)$fields;

        foreach ($fields as $f) {
            if ($this->has($f)==false || empty($f)==true || (is_array($f)==true&&count($f)==0)) {
                $errors[] = $f;
            }
        }

        if (isset($errors) && count($errors)>0) {

            $this->return("O(s) campo(s) <b>".implode(", ", $errors)."</b> são obrigatórios");
            exit;
        }
    }

    /**
     * requiredOne function
     * Pelo menos um dos campos passados na lista deve ser obrigatório
     *
     * @param array $fields
     * @return void
     */
    public function requiredOne($fields)
    {

        $fields = (array)$fields;

        $error = 0;
        $success = 0;

        foreach ($fields as $f) {

            if ($this->contain($f)) {
                $success++;
            } else {
                $error++;
            }
        }

        if ($success==0) {
            $this->return("O(s) campo(s) <b>".implode(" ou ", $fields)."</b> devem ser obrigatórios");
            exit;
        }
    }

    /**
     * allow function
     * Lista de campos permitidos, qualquer outro gerará erro
     *
     * @param array $fields
     * @return void
     */
    public function allow($fields)
    {
        $fields = (array)$fields;

        foreach ($this->data as $d => $v) {

            if (in_array($d, $fields)==false) {

                $errors[] = $d;
            }
        }

        if (isset($errors) && count($errors)>0) {

            $this->return("O(s) campo(s) ".implode(", ", $errors)." são inválidos");
        }

        return $this;
    }

    /**
     * Validações
     */

    public function id($name=null, $remove=false)
    {

        $name = $name ?? "id";

        $id = $this->data[$name];

        if (!$id) {

            $this->return("Id não encontrado");
            exit;
        }

        // Validar ID
        if (v::intVal()->validate($id)==false) {

            $this->return("Id Inválido");
            exit;
        }

        if ($remove) {
            unset($this->data[$name]);
        }

        return $id;
    }

    public function csrf()
    {
        if ($this->has("csrf")) {

            if ( Csrf::confirm($this->data["csrf"]) == false) {

                $this->return("Formulário enviado inválido!");
                exit;
            }

            $this->remove("csrf");
        }
    }

    public function is_int($fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)==true && v::intVal()->validate($this->data[$f])==false) {
                $errors[] = $f;
            }
        }

        if (isset($errors) && count($errors)>0) {

            $this->return("O(s) campo(s) ".implode(", ", $errors)." devem ser do tipo inteiro");
        }

        return $this;
    }

    public function is_bool($fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)==true && v::boolVal()->validate($this->data[$f])==false) {
                $errors[] = $f;
            }
        }

        if (isset($errors) && count($errors)>0) {

            $this->return("O(s) campo(s) ".implode(", ", $errors)." devem ser do tipo verdadeiro/falso");
        }

        return $this;
    }

    public function is_array($fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)==true && !is_array($this->data[$f])) {
                $errors[] = $f;
            }
        }

        if (isset($errors) && count($errors)>0) {

            $this->return("O(s) campo(s) ".implode(", ", $errors)." devem ser do tipo array");
        }

        return $this;
    }

    public function is_numeric($fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)==true && v::floatVal()->validate($this->data[$f])==false) {
                $errors[] = $f;
            }
        }

        if (isset($errors) && count($errors)>0) {

            $this->return("O(s) campo(s) ".implode(", ", $errors)." devem ser do tipo numérico");
        }

        return $this;
    }

    public function is_email($fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)==true && v::email()->validate($this->data[$f])==false) {
                $errors[] = $f;
            }
        }

        if (isset($errors) && count($errors)>0) {

            $this->return("O(s) campo(s) ".implode(", ", $errors)." devem ser do tipo mail");
        }

        return $this;
    }

    public function is_date($fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)==true && v::date()->validate($this->data[$f])==false) {
                $errors[] = $f;
            }
        }

        if (isset($errors) && count($errors)>0) {

            $this->return("O(s) campo(s) ".implode(", ", $errors)." devem ser do tipo data");
        }

        return $this;
    }

    public function list($fields, $list)
    {
        $fields = (array)$fields;
        $list   = (array)$list;

        foreach ($fields as $f) {

            if ($this->has($f)==true) {
                if (in_array( $this->data[$f] , $list ) === false) {
                    $errors[] = $f;
                }
            }
        }

        if (isset($errors) && count($errors)>0) {

            $this->return("O(s) campo(s) ".implode(", ", $errors)." possuem valores inválidos ");
        }

        return $this;
    }

    public function max($fields, $length)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)==true && v::max($length)->validate($this->data[$f])==false) {
                $errors[] = $f;
            }
        }

        if (isset($errors) && count($errors)>0) {

            $this->return("O(s) campo(s) ".implode(", ", $errors)." devem ser menores ou iguais à ".$length);
        }

        return $this;
    }

    public function min($fields, $length)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)==true && v::min($length)->validate($this->data[$f])==false) {
                $errors[] = $f;
            }
        }

        if (isset($errors) && count($errors)>0) {

            $this->return("O(s) campo(s) ".implode(", ", $errors)." devem ser maiores ou iguais à ".$length);
        }

        return $this;
    }


    /**
     * Conversões
     */

    public function upper($fields)
    {

        return $this->convert_case($fields, MB_CASE_UPPER);
    }

    public function lower($fields)
    {
        return $this->convert_case($fields, MB_CASE_LOWER);
    }

    public function capitalize($fields)
    {
        return $this->convert_case($fields, MB_CASE_TITLE);
    }

    private function convert_case($fields, $mode = MB_CASE_UPPER)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)) {
                $this->data[$f] = mb_convert_case(trim($this->data[$f]), $mode, "UTF-8");
            }
        }

        return $this;
    }

    public function date($fields, $force=false)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)) {

                if ($this->is_datebr($this->data[$f])) {

                    $this->data[$f] = implode('-',array_reverse(explode('/', $this->data[$f])));
                }
                else {

                    $this->data[$f] = date('Y-m-d', strtotime($this->data[$f]));
                }

            }
            elseif ($force) {

                $this->data[$f] = date("Y-m-d");
            }

        }

        return $this;
    }


    public function datetime($fields, $force=false)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)) {

                if ($this->is_datebr($this->data[$f], true)) {

                    $datetime = explode(" ", $this->data[$f]);
                    $hora = $datetime[1] ?? '00:00:00';
                    $this->data[$f] = implode('-',array_reverse(explode('/', $datetime[0])))." ".$hora;
                }
                else {

                    $this->data[$f] = date('Y-m-d H:i:s', strtotime($this->data[$f]));
                }

            }
            elseif ($force) {

                $this->data[$f] = date("Y-m-d H:i:s");
            }
        }

        return $this;
    }

    public function seconds($fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)) {

                $time = explode(":", $this->data[$f]);

                $hour    = $time[0] * 3600;
                $minutes = $time[1] * 60;
                $seconds = $time[2] ?? 0;

                $this->data[$f] = $hour + $minutes + $seconds;
            }
        }

        return $this;
    }

    public function merge($new, $fields, $remove=true)
    {
        $fields = (array)$fields;
        $this->data[$new] = implode(" ", $fields);

        if ($remove==true)
            $this->remove($fields);

        return $this;
    }

    public function numbers($fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)) {
                $this->data[$f] = preg_replace('/[^\d]/', '',$this->data[$f]);
            }
        }

        return $this;
    }

    public function string2Int($fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)) {
                $this->data[$f] = (int)$this->data[$f];
            }
        }

        return $this;
    }

    public function decimal2Float($fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)) {
                $this->data[$f] = str_replace(",", ".", $this->data[$f]);
            }
        }

        return $this;
    }

    public function money2Float($fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)) {
                $this->data[$f] = str_replace(",", ".", str_replace(".", "", $this->data[$f]));
            }
        }

        return $this;
    }

    public function password($fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)) {
                $this->data[$f] = Password::hash($this->data[$f]);
            }
        }

        return $this;
    }

    public function url($fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)) {
                $this->data[$f] = str_replace(["https://", "http://"], ["",""], $this->data[$f]);
            }
        }

        return $this;
    }

    public function ifEmpty($array)
    {

        foreach ($array as $field => $value) {

            $this->data[$field] = $this->has($field)==true ? $this->data[$field] : $value;
        }

        return $this;
    }

    public function zeroIfEmpty($fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {

            $this->data[$f] = $this->has($f)==true ? $this->data[$f] : 0;
        }

        return $this;
    }

    public function nullIfEmpty($fields)
    {

        $fields = $fields=="*" ? array_keys($this->data) : (array)$fields;

        foreach ($fields as $f) {

            if ($this->has($f)==true) {

                if ($this->data[$f] == empty($this->data[$f]) || $this->data[$f] === '') {

                    $this->data[$f] = null;

                } else {

                    $this->data[$f] = $this->data[$f];
                }
            } else {

                $this->data[$f] = null;
            }
        }

        return $this;
    }

    public function nullIfExists($fields)
    {

        $fields = $fields=="*" ? array_keys($this->data) : (array)$fields;

        foreach ($this->data as $f => $v) {

            if (!$v) {

                if (!in_array($f, $fields)) {

                    unset($this->data[$f]);
                }
            }
        }

        return $this;
    }

    public function timestamp($fields, $sec=true)
    {
        $fields = (array)$fields;

        $format = $sec ? "Y-m-d H:i:s" : "Y-m-d";
        foreach ($fields as $f) {
            $this->data[$f] = date($format);
        }

        return $this;
    }

    public function unique($fields)
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {
            $this->data[$f] = array_unique($this->data[$f]);
        }

        return $this;
    }

    public function serialize($fields, $sep=",")
    {
        $fields = (array)$fields;

        foreach ($fields as $f) {
            if ($this->has($f)) {

                if (!is_array($this->data[$f]))
                    $this->data[$f] = serialize(explode($sep, str_replace(" ","",$this->data[$f])));
                else
                    $this->data[$f] = serialize($this->data[$f]);
            }
        }

        return $this;
    }

    public function token($fields, $algo="sha1", $length=null, $key=null, $max=255)
    {
        $fields = (array)$fields;

        switch ($algo) {

            case 'sha1':
                $hash = sha1($key??time());
            break;

            case 'md5':
                $hash = md5($key??time());
            break;

            case 'base64':
                $hash = base64_encode($key??time());
            break;

            default:
                $hash = sha1($key??time());
            break;
        }

        if ($length>0) {

            $hash = substr($hash, 0, $length);
        }

        $hash = substr($hash, 0, $max);

        foreach ($fields as $f) {
            $this->data[$f] = $hash;
        }

        return $this;
    }

    public function print()
    {
        echo "<pre>", print_r($this->data, 1), "</pre>";
        die();
    }

    /* Retorno de Mensagens */

    private function return($message)
    {
        if ($this->response=="json") {

            echo response_json(["error"=>strip_tags($message)], $this->errCode);
            exit;
        }
        else if ($this->response=="exception") {

            throw new Exception($message);
            exit;
        }
        else {

            (new Message())->flash($message, "danger", true);
            (new Redirect())->referer();
        }
    }

    private function is_datebr($date, $time=false, $sec=false)
    {

        if ($time==true) {

            $datetime = explode(" ", $date);
            $dt = $datetime[0];

            if (isset($datetime[1]))
                $tm = $datetime[1];

        } else {

            $dt = $date;
        }

        if (!$dt)
            return false;

        if ($time==true && !isset($tm))
            return false;

        if (substr($dt, 2, 1)!="/" && substr($dt, 5, 1)!="/")
            return false;

        $dt = explode("/", $dt);

        if (count($dt)<3)
            return false;

        if ($time==true) {

            $tm = explode(":", $tm);

            $parts = $sec ? 3 : 2;

            if (count($tm)<$parts)
                return false;
        }

        return true;

    }


}

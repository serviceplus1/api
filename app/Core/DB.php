<?php
namespace App\Core;

use PDO;
use PDOException;

class DB extends Connection
{

    protected static $table;
    protected static $alias;
    protected static $pdo;
    protected static $sql;
    protected static $data;

    public function __construct()
    {
        self::$pdo = parent::connect();
    }

    public static function setConnection($type)
    {
        if (!$type)
            die("Você deve informar o tipo de conexão");

        self::$pdo = parent::connect($type);
    }

    private static function setFetchStyle($style, $qry)
    {

        switch (strtolower($style)) {

            case 'obj':
            case 'object':
                $fetch = $qry->fetchAll(PDO::FETCH_OBJ);
            break;

            case 'array':
            case 'assoc':
                $fetch = $qry->fetchAll(PDO::FETCH_ASSOC);
            break;

            case 'class':
                $fetch = $qry->fetchAll(PDO::FETCH_CLASS);
            break;

            case 'bound':
                $fetch = $qry->fetchAll(PDO::FETCH_BOUND);
            break;

            case 'into':
                $fetch = $qry->fetchAll(PDO::FETCH_INTO);
            break;

            case 'lazy':
                $fetch = $qry->fetchAll(PDO::FETCH_LAZY);
            break;

            case 'named':
                $fetch = $qry->fetchAll(PDO::FETCH_NAMED);
            break;

            case 'num':
                $fetch = $qry->fetchAll(PDO::FETCH_NUM);
            break;

            case 'props_late':
                $fetch = $qry->fetchAll(PDO::FETCH_PROPS_LATE);
            break;

            case 'unique':
                $fetch = $qry->fetchAll(PDO::FETCH_UNIQUE);
            break;

            case 'key_pair':
            case 'keypair':
                $fetch = $qry->fetchAll(PDO::FETCH_KEY_PAIR);
            break;

            default:
                $fetch = $qry->fetchAll(PDO::FETCH_OBJ);
            break;
        }

        return $fetch;
    }

    private static function renameTable($table)
    {
        $table = strtolower($table);

        if (preg_match("/ as /", $table)) {

            $exp = explode(" as ", $table);

            $table = "`".trim($exp[0])."`".
                (trim($exp[1]) ? " AS ". trim($exp[1]) : "");

        } else {

            $table = "`".$table."`";
        }

        return $table;
    }

    public static function table($table)
    {
        static::$table = self::renameTable($table);
        return new self;
    }

    protected static function prefix()
    {
        $prefix = preg_match("/SELECT/", static::$sql)
                            ? ""
                            : "SELECT * FROM " . static::$table . (static::$alias ? " AS " . static::$alias : "");
        static::$sql = $prefix.static::$sql;
        return new self;
    }

    public static function clear()
    {
        static::$sql = null;
        return new self;
    }

    public static function get($clear=true, $style=null)
    {

        self::prefix();

        try {

            $qry = self::$pdo->prepare(static::$sql);
            $qry->execute();

            if ($clear==true) {
                static::$sql = null;
            }

            $result = self::setFetchStyle($style, $qry);

            $qry->closeCursor();

            return $result;

        } catch (PDOException $e) {

            die("get: ".$e->getMessage()." (".static::$sql.")");
        }
    }

    public static function execute()
    {
        try {

            $qry = self::$pdo->prepare(static::$sql);
            $exec = $qry->execute();

            static::$sql = null;

            return $exec ? true : false;

        } catch (PDOException $e) {

            die("exec: ".$e->getMessage()." (".static::$sql.")");
        }
    }

    public static function pluck($column)
    {
        self::prefix();

        static::$sql = str_replace('SELECT *', 'SELECT '.$column, static::$sql);

        try {

            $qry = self::$pdo->prepare(static::$sql);
            $qry->execute();

            $dados = $qry->fetchAll();
            $count = $qry->rowCount();
            $qry->closeCursor();

            if ($count>0) {

                $column = strpos($column, '.')
                    ? explode(".", $column)[1]
                    : $column;

                foreach ($dados as $dado) {
                    $collection[] = $dado->{$column};
                }

            } else {

                $collection = null;
            }

            static::$sql = null;

            return $collection;

        } catch (PDOException $e) {

            die("pluck: ".$e->getMessage()." (".static::$sql.")");
        }
    }

    public static function total(): int
    {
        self::prefix();

        try {

            $qry = self::$pdo->prepare(static::$sql);
            $qry->execute();

            static::$sql = null;

            return $qry->rowCount();

        } catch (PDOException $e) {

            die("total: ".$e->getMessage()." (".static::$sql.")");
        }
    }

    public static function toSql()
    {
        if (preg_match("/SELECT/", static::$sql)==false) {
            self::prefix();
        }

        echo static::$sql;
        static::$sql = null;
    }

    public static function group($column)
    {

        if (is_array($column)) {

            $group = implode(",",$column);

        } else {

            $group = $column;
        }

        self::prefix();

        $prefix = preg_match("/GROUP BY/", self::$sql) ? "," : " GROUP BY ";

        self::$sql .= $prefix." $group";
        return new self;
    }

    public static function having($column, $value, $operator=null)
    {

        $o = $operator ?? '=';

        static::$sql .= " HAVING $column $o '$value' ";
        return new self;
    }

    public static function order($column=null, $sort='ASC')
    {
        self::prefix();

        $column = isset($column) ? $column : ' ordem ';
        $prefix = preg_match("/ORDER BY/", static::$sql) ? "," : " ORDER BY ";

        static::$sql .= $prefix." $column $sort ";
        return new self;
    }

    public static function orderBy($column=null, $sort='ASC')
    {
        return self::order($column, $sort);
    }

    public static function orderAsc($column=null)
    {
        return self::order($column, 'ASC');
    }

    public static function orderDesc($column=null)
    {
        return self::order($column, 'DESC');
    }

    public static function orderField($column, $array, $sort='ASC')
    {
        self::prefix();

        $column = isset($column) ? $column : ' ordem ';
        $prefix = preg_match("/ORDER BY/", static::$sql) ? "," : " ORDER BY ";

        static::$sql .= $prefix."
                        FIELD($column , '".implode('\',\'', $array)."')
                        $sort ";
        return new self;
    }

    public static function orderByField($column, $array, $sort='ASC')
    {
        return self::orderField($column, $array, $sort);
    }

    public static function orderRaw($statement)
    {
        self::prefix();

        $prefix = preg_match("/ORDER BY/", static::$sql) ? "," : " ORDER BY ";
        static::$sql .= $prefix." ".$statement;
        return new self;
    }

    public static function orderByRaw($statement)
    {
        return self::orderRaw($statement);
    }

    public static function rand()
    {
        self::prefix();

        static::$sql .= " ORDER BY RAND() ";
        return new self;
    }

    public static function random()
    {
        self::prefix();

        static::$sql .= " ORDER BY RANDOM() ";
        return new self;
    }

    public static function first($column=null)
    {
        self::prefix();

        static::$sql .= (isset($column)
                            ? " ORDER BY ".$column. " ASC "
                            : ""
                        )." LIMIT 1 OFFSET 0";

        $cont = self::$pdo->query(static::$sql)->rowCount();

        if ($cont>0) {

            static::$data = ((array)self::get())[0];
            static::$sql = null;
            return static::$data;

        } else {

            static::$sql = null;
            return null;
        }
    }

    public static function last($column=null)
    {
        self::prefix();

        static::$sql .= " ORDER BY ".($column ?? "id"). " DESC LIMIT 1 OFFSET 0";

        $cont = self::$pdo->query(static::$sql)->rowCount();

        if ($cont>0) {

            static::$data = ((array)self::get())[0];
            static::$sql = null;
            return static::$data;

        } else {

            static::$sql = null;
            return null;
        }
    }

    public static function limit($limit, $offset=null)
    {
        self::prefix();

        $o = $offset ?? 0;

        static::$sql .= " LIMIT $limit OFFSET $o ";
        return new self;
    }

    public static function offset($start)
    {

        self::prefix();

        if (preg_match("/OFFSET 0/", static::$sql)) {
            static::$sql = str_replace("OFFSET 0", "", static::$sql);
        }

        $limit = "18446744073709551615";

        $prefix = preg_match("/LIMIT/", static::$sql)
                            ? static::$sql .= " OFFSET $start "
                            : static::$sql .= " LIMIT {$limit} OFFSET $start ";

        return new self;
    }

    public static function select($columns='*')
    {
        if ($columns!='*') {
            $columns = is_array($columns) ? implode(',', $columns) : $columns;
            self::prefix();
        }

        static::$sql = str_replace('SELECT *', 'SELECT '.$columns, static::$sql);
        return new self;
    }

    public static function distinct($column) {

        self::prefix();

        static::$sql = str_replace('SELECT *', 'SELECT DISTINCT('.$column.')', static::$sql);

        return new self;
    }

    public static function cont($column=null, $alias='total')
    {
        $column = isset($column) ? $column : 'id';

        self::prefix();
        static::$sql = str_replace(
            'SELECT *',
            'SELECT COUNT('.$column.') AS '.$alias.' ',
            static::$sql
        );

        return ((array)self::get())[0]->$alias;
    }

    public static function max($column, $alias='max')
    {
        self::prefix();
        static::$sql = str_replace(
            'SELECT *',
            'SELECT MAX('.$column.') AS '.$alias.' ',
            static::$sql
        );

        return ((array)self::get())[0]->$alias;
    }

    public static function min($column, $alias='min')
    {
        self::prefix();
        static::$sql = str_replace(
            'SELECT *',
            'SELECT MIN('.$column.') AS '.$alias.' ',
            static::$sql
        );

        return ((array)self::get())[0]->$alias;
    }

    public static function avg($column, $alias='avg')
    {
        self::prefix();
        static::$sql = str_replace(
            'SELECT *',
            'SELECT AVG('.$column.') AS '.$alias.' ',
            static::$sql
        );

        return ((array)self::get())[0]->$alias;
    }

    public static function sum($column, $alias='sum')
    {
        self::prefix();
        static::$sql = str_replace(
            'SELECT *',
            'SELECT SUM('.$column.') AS '.$alias.' ',
            static::$sql
        );

        return ((array)self::get())[0]->$alias;
    }

    public static function join($table, $column, $rel_column, $operator=null)
    {

        self::prefix();

        $o  = $operator ?? '=';

        $table = self::renameTable($table);

        static::$sql .= " INNER JOIN $table ON $column $o $rel_column ";
        return new self;
    }

    public static function leftJoin($table, $column, $rel_column, $operator=null)
    {
        $o  = $operator ?? '=';

        self::prefix();

        $table = self::renameTable($table);

        static::$sql .= " LEFT JOIN $table ON $column $o $rel_column ";
        return new self;
    }

    public static function rightJoin($table, $column, $rel_column, $operator=null)
    {
        $o  = $operator ?? '=';

        self::prefix();

        $table = self::renameTable($table);

        static::$sql .= " RIGHT JOIN $table ON $column $o $rel_column ";
        return new self;
    }

    public static function all()
    {
        return self::get();
    }

    public static function actives($column=null, $value=1)
    {
        $column = $column ?? 'status';
        $value  = is_int($value) ? (int)$value : "'".$value."'";

        self::prefix();
        self::statement();

        static::$sql .= " $column = $value ";
        return new self;
    }

    public static function visibles($column=null, $value=1)
    {
        $column = $column ?? 'visivel';
        $value  = is_int($value) ? (int)$value : "'".$value."'";

        return self::actives($column, $value);
    }

    public static function find($value, $column=null)
    {

        $c = $column ?? 'id';

        self::prefix();
        self::statement();

        // static::$sql = " SELECT * FROM ".static::$table." WHERE $c = '$value' ";

        static::$sql .= " $c = '$value' ";

        $cont = self::$pdo->query(static::$sql)->rowCount();

        if ($cont>0) {

            static::$data = ((array)self::get())[0];

            static::$sql = null;

            return static::$data;

        } else {

            static::$sql = null;
            return null;
        }
    }

    public static function statement($condition = " AND ")
    {
        self::prefix();

        if ($condition) {

            static::$sql .= preg_match("/WHERE/", static::$sql)
                ? $condition
                : " WHERE ";
        }
        return new self;
    }

    public static function where($column, $value, $operator=null, $condition="AND") {

        $o = $operator ?? '=';

        self::statement( $condition );

        static::$sql .= " $column $o '$value' ";

        return new self;
    }

    public static function orWhere($column, $value, $operator=null)
    {
        $o = $operator ?? '=';

        self::statement("OR");
        static::$sql .= " $column $o '$value' ";

        return new self;
    }

    public static function different($column, $value, $condition=" AND ") {

        self::statement( $condition );

        static::$sql .= " $column <> '$value' ";

        return new self;
    }

    public static function whereIn($array, $column=null, $condition = "AND")
    {

        $c = $column ?? 'id';
        $a = (array)$array;

        self::statement($condition);
        static::$sql .= " $c IN ('".implode('\',\'', $a)."') ";

        return new self;
    }

    public static function whereNotIn($array, $column=null, $condition = "AND")
    {
        $c = $column ?? 'id';
        $a = (array)$array;

        self::statement($condition);
        static::$sql .= " $c NOT IN ('".implode('\',\'', $a)."') ";

        return new self;
    }

    public static function isNull($column, $condition = "AND")
    {
        self::statement($condition);
        static::$sql .= " $column IS NULL ";
        return new self;
    }

    public static function isNotNull($column, $condition="AND")
    {
        self::statement($condition);
        static::$sql .= " $column IS NOT NULL ";
        return new self;
    }

    public static function match()
    {
        $args = func_get_args();
        self::statement();
        static::$sql .= " MATCH(" . implode(', ', $args) . ") ";
        return new self;
    }

    public static function against($search_terms)
    {
        static::$sql .= " AGAINST('$search_terms' IN BOOLEAN MODE)";
        return new self;
    }

    public static function like($column, $value, $operator="AND")
    {
        self::statement($operator);
        static::$sql .= " $column LIKE '%{$value}%' ";
        return new self;
    }

    public static function startLike($column, $value, $operator="AND")
    {
        self::statement($operator);
        static::$sql .= " $column LIKE '{$value}%' ";
        return new self;
    }

    public static function endLike($column, $value, $operator="AND")
    {
        self::statement($operator);
        static::$sql .= " $column LIKE '%{$value}' ";
        return new self;
    }

    public static function replaceLike($column, $value, $operator="AND", $search=" ", $replace="%")
    {
        self::statement($operator);
        static::$sql .= " $column LIKE REPLACE('{$value}', '{$search}', '{$replace}')";
        return new self;
    }

    public static function rlike($column, $value, $operator="AND", $regex="^")
    {
        $values = explode(" ", $value);

        $val = "";
        foreach ($values as $v) {
            $val .= "({$regex}{$v})";
        }

        self::statement($operator);
        static::$sql .= " $column RLIKE '{$val}' ";
        return new self;
    }

    public static function manyLike($column, $value, $operator="AND")
    {

        $values = explode(" ", $value);
        $values = array_filter($values, 'strlen');
        $values = array_values($values);

        $qry = "(";
        for ($i=0; $i<count($values); $i++) {

            if ($i>0)
                $qry .= " AND ";

            $qry .= "{$column} LIKE '%{$values[$i]}%'";
        }
        $qry .= ") ";

        self::statement($operator);
        static::$sql .= $qry;
        return new self;
    }

    public static function between($column, $start, $end, $operator="AND")
    {
        self::statement($operator);
        static::$sql .= " $column BETWEEN '$start' AND '$end' ";
        return new self;
    }

    public static function queryRaw($statement)
    {
        static::$sql = $statement;
        return new self;
    }

    public static function raw($statement)
    {
        static::$sql .= $statement;
        return new self;
    }

    public static function increment($column, $value=1)
    {
        self::prefix();

        $statement = "UPDATE ".static::$table." SET $column = ($column+$value)";

        $sql = preg_match("/SELECT */", static::$sql)
            ? str_replace("SELECT * FROM ".static::$table, $statement, static::$sql)
            : "";

        try {

            $qry = self::$pdo->prepare($sql);
            $commit = $qry->execute();

            static::$sql = null;
            return $commit ? true : false;

        } catch (PDOException $e) {
            die("increment: " . $e->getMessage()." (".static::$sql.")");
        }
    }

    public static function decrement($column, $value=1)
    {
        self::prefix();

        $statement = "UPDATE ".static::$table." SET $column = ($column-$value)";

        $sql = preg_match("/SELECT */", static::$sql)
            ? str_replace("SELECT * FROM ".static::$table, $statement, static::$sql)
            : "";

        try {

            $qry = self::$pdo->prepare($sql);
            $commit = $qry->execute();

            static::$sql = null;

            return $commit ? true : false;

        } catch (PDOException $e) {

            die("decrement: " . $e->getMessage()." (".static::$sql.")");
        }
    }

    public static function insert($request)
    {
        $values  = is_array($request) ? $request : (array)$request;
        $columns = array_keys($values);

        $sql  = "INSERT INTO "
            .static::$table." (".implode(',', $columns).")
            VALUES (:".implode(',:', $columns).")";

        try {

            $qry = self::$pdo->prepare($sql);

            foreach ($request as $k => $v) {

                $qry->bindValue(":".$k, $v);
            }

            $commit = $qry->execute($values);
            $qry->closeCursor();

            if ($commit) {

                $response['id'] = (int)self::$pdo->lastInsertId();
                $response['result'] = true;

            } else {

                $response['result'] = false;
            }

            static::$sql = null;
            return (object)$response;

        } catch (PDOException $e) {

            die("ins: tabela(".static::$table."): " . $e->getMessage()." (".static::$sql.")");
        }
    }

    public function insertMany(array $columns, array $values)
    {

        $columns  = (array)$columns;

        $sql  = "INSERT INTO " .self::$table." (".implode(',', $columns).")";
        $sql .= " VALUES ";

        foreach ($values as $v) {

            $sql_values[] = "('" . implode("','", $v) . "')";
        }

        $sql .= implode(",", $sql_values);

        try {

            $qry = self::$pdo->prepare($sql);
            $commit = $qry->execute();
            $qry->closeCursor();

            self::$sql = null;

            return $commit;

        } catch (PDOException $e) {

            die("ins many: " . $e->getMessage()." (".self::$sql.")");
        }
    }

    static function update($request, $column=null, $operator=null, $value=null)
    {

        $values = is_array($request) ? $request : (array)$request;
        $fields = array_keys($values);

        $params = '';

        self::prefix();

        if (preg_match("/WHERE/", static::$sql)) {

            for ($i=0; $i<count($fields); $i++) {

                $params .= $fields[$i].'=:'.$fields[$i].',';
            }

            $query = explode(" WHERE ", self::$sql);

            $sql  = preg_match("/SELECT */", static::$sql) ? str_replace( "SELECT * FROM ", "UPDATE ", $query[0]) : "";
            $sql .= " SET ".substr($params, 0, -1);
            $sql .= " WHERE ".trim($query[1]);

        }
        // elseif (preg_match("/WHERE/", static::$sql)) {

        //     for ($i=0; $i<count($fields); $i++) {

        //         $params .= $fields[$i].'=:'.$fields[$i].',';
        //     }

        //     $statement = "UPDATE ".static::$table." SET ".substr($params, 0, -1);

        // }
        elseif (isset($column) && isset($column)) {

            for ($i=0; $i<count($fields); $i++) {

                $params .= $fields[$i].'=:'.$fields[$i].',';
            }

            $sql = "UPDATE ".static::$table.
                   " SET ".substr($params, 0, -1).
                   " WHERE ".$column." ".$operator." '".$value."'";

        } else {

            for ($i=1; $i<count($fields); $i++) {

                $params .= $fields[$i].'=:'.$fields[$i].',';
            }

            $sql = "UPDATE ".static::$table.
                   " SET ".substr($params, 0, -1).
                   " WHERE ".$fields[0].' = :'.$fields[0];
        }

        try {

            $qry = self::$pdo->prepare($sql);
            $commit = $qry->execute($values);

            static::$sql = null;

            return $commit ? true : false;

        } catch (PDOException $e)
        {

            die("up: " . $e->getMessage()." (".static::$sql.")");
        }
    }

    public static function delete($value=null, $column=null, $operator=null)
    {
        if ($value) {

            $c = $column ?? "id";
            $o = $column ?? "=";

            $sql = "DELETE FROM ".static::$table." WHERE $c $o $value ";

        } else {

            self::prefix();
            $sql = preg_match("/SELECT */", static::$sql)
                ? str_replace("SELECT *", "DELETE", static::$sql)
                : "";
        }

        try {

            $qry = self::$pdo->prepare($sql);
            $commit = $qry->execute();
            $qry->closeCursor();

            static::$sql = null;

            return $commit ? true : false;

        } catch (PDOException $e) {

            die("del: " . $e->getMessage()." (".static::$sql.")");
        }
    }

    public static function truncate()
    {
        $sql = "TRUNCATE ".static::$table;

        try {

            $qry = self::$pdo->prepare($sql);
            $commit = $qry->execute();

            return $commit ? true : false;

        } catch (PDOException $e) {

            die("truncate: " . $e->getMessage()." (".static::$sql.")");
        }
    }

    public static function agroup($condition = " AND ")
    {
        if ($condition) {

            static::$sql .= $condition." (";

        } else {

            static::$sql .= " (";
        }

        return new self;
    }

    public static function endAgroup()
    {
        static::$sql .= ") ";
        return new self;
    }

    public static function today($column="data", $condition="AND")
    {
        self::statement($condition);

        static::$sql .= " {$column} = DATE(NOW()) ";
        return new self;
    }

    public static function fetch($style=null) {

        try {

            $qry = self::$pdo->prepare(static::$sql);
            $qry->execute();

            $result = self::setFetchStyle($style, $qry);

            static::$sql = null;

            $qry->closeCursor();

            return $result;

        } catch (PDOException $e) {

            die("get: ".$e->getMessage()." (".static::$sql.")");
        }
    }

    public static function count(): int
    {
        return self::total();
    }

    public static function in($array, $column=null, $condition = "AND")
    {
        return self::whereIn($array, $column, $condition);
    }

    public static function notIn($array, $column=null, $condition = "AND")
    {
        return self::whereNotIn($array, $column, $condition);
    }

    public static function or($column, $value, $operator=null)
    {
        return self::orWhere($column, $value, $operator);
    }

    public static function fetchRaw($query, $style=null)
    {

        try {

            $qry = self::$pdo->prepare($query);
            $qry->execute();

            $result = self::setFetchStyle($style, $qry);

            $qry->closeCursor();

            return $result;

        } catch (PDOException $e) {

            die("fetchRaw: ".$e->getMessage()." (".$query.")");
        }

    }

}

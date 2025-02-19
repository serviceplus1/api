<?php

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

class Equipamento extends Model
{

    static $table = "clientes_equipamentos";
    static $alias = "e";
    static $primary = "maquina_id";

    static $fields = [
            'e.maquina_id AS maquina_id',
            'e.cliente_id AS cliente_id',
            'cl.nome AS cliente_nome',
            'cl.endereco AS cliente_endereco',
            'cl.numero AS cliente_numero',
            'cl.complemento AS cliente_complemento',
            'cl.bairro AS cliente_bairro',
            'cl.cidade AS cliente_cidade',
            'cl.estado AS cliente_estado',
            'cl.cep AS cliente_cep',
            'cl.telefone AS cliente_telefone',
            'cl.contato AS cliente_contato',
            'e.local AS maquina_local',
            'e.numero AS maquina_numero',
            'e.status AS maquina_status',
            'e.contador AS maquina_contador',
            'p.produto_id AS produto_id',
            'p.nome AS produto_nome',
            'p.contador_mono AS produto_contador_mono',
            'p.contador_color AS produto_contador_color',
            'CONCAT("https://app.serviceplus.com.br/storage/media/'.TOKEN_CLIENT.'/produtos/", f.arquivo) as produto_foto',
            'c.categoria_nome AS produto_categoria',
            'm.marca_nome AS produto_marca',
            's.descricao AS status_descricao',
            's.cor AS status_cor',
            's.abertura AS status_abertura',
    ];

    public static function findById($id)
    {
        $equipamento  = Equipamento::leftJoin('clientes AS cl', 'e.cliente_id', 'cl.cliente_id')
                        ->join('produtos AS p', 'e.produto_id', 'p.produto_id')
                        ->leftJoin('produtos_fotos AS f', 'f.produto_id', 'p.produto_id')
                        ->join('equipamentos_status AS s', 'e.status', 's.id')
                        ->join('categorias AS c', 'p.categoria_id', 'c.categoria_id')
                        ->join('marcas AS m', 'p.marca_id', 'm.marca_id')
                        ->select(self::$fields)
                        ->where('e.'.self::$primary, $id)
                        ->first();

        return $equipamento;
    }

    public static function findBySerialNumber($serial_number)
    {
        $equipamento  = Equipamento::leftJoin('clientes AS cl', 'e.cliente_id', 'cl.cliente_id')
                        ->join('produtos AS p', 'e.produto_id', 'p.produto_id')
                        ->leftJoin('produtos_fotos AS f', 'f.produto_id', 'p.produto_id')
                        ->join('equipamentos_status AS s', 'e.status', 's.id')
                        ->join('categorias AS c', 'p.categoria_id', 'c.categoria_id')
                        ->join('marcas AS m', 'p.marca_id', 'm.marca_id')
                        ->select(self::$fields)
                        ->where('e.numero', $serial_number)
                        ->get();

        return $equipamento;
    }


}

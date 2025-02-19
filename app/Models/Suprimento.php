<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Model;

class Suprimento extends Model
{

    static $table = "suprimento";

    static $primary = "id";

    public static function listByEquipamento($equipamento_id)
    {

        // $auth = new Auth("clientes");
        // $user = $auth->user();

        // $cliente = Cliente::findById($user->cliente_id);

        $equipamento = Equipamento::findById($equipamento_id);

        $fields = [
                    's.id',
                    's.nome',
                    's.valor',
                    's.sku',
                    's.cod_fabricante',
                    'CONCAT("https://app.serviceplus.com.br/storage/media/'.TOKEN_CLIENT.'/suprimentos/", s.imagem) as foto',
        ];

        $suprimentos = DB::table('produtos_suprimentos as ps')
            ->join('suprimento as s', 'ps.suprimento_id', 's.id')
            ->where('ps.produto_id', $equipamento->produto_id)
            ->select($fields)
            ->get();

        return [$suprimentos];
    }

    public static function listByChamado($chamado_id, $baixado=0)
    {

        $fields = [
            'cs.id as id',
            'cs.suprimento_id as suprimento_id',
            's.nome as suprimento_nome',
            'CONCAT("'.URL_APP.'/storage/media/'.TOKEN_CLIENT.'/suprimentos/", s.imagem) as suprimento_foto',
            'cs.quantidade as quantidade',
            'cs.quantidade_enviada as quantidade_enviada',
            'cs.valor as valor',
        ];

        $suprimentos = DB::table('chamados_suprimentos as cs')
            ->join('suprimento as s', 'cs.suprimento_id', 's.id')
            ->where('cs.chamado_id', $chamado_id);

        if (!is_null($baixado))
            $suprimentos = $suprimentos->where("cs.baixado", $baixado);

        $suprimentos = $suprimentos->select($fields)
            ->get();

        return $suprimentos;
    }

}

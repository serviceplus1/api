<?php

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

class Peca extends Model
{

    static $table = "pecas";
    static $alias = "p";
    static $primary = "id";

    public static function listByEquipamento($equipamento_id, $search=false)
    {

        $equipamento = Equipamento::findById($equipamento_id);

        $pecas = DB::table('produtos_pecas as pp')
            ->join('pecas as p', 'pp.peca_id', 'p.id')
            ->where('pp.produto_id', $equipamento->produto_id);

        if ($search) {

            $pecas = $pecas->agroup()
                ->like('p.nome', $search, null)
                ->like('p.pn', $search, "OR")
                ->endAgroup();
        }

        $pecas = $pecas->select('p.*')->order("p.nome")->get();

        return $pecas;
    }

    public static function listByChamado($chamado_id, $baixado=0)
    {

        $pecas = DB::table('chamados_pecas as cp')
            ->join('pecas as p', 'cp.peca_id', 'p.id')
            ->where('cp.chamado_id', $chamado_id);

        if (!is_null($baixado)) {

            $pecas = $pecas->where("cp.baixado", $baixado);
        }

        $pecas = $pecas->select(
        [
                    'cp.id as id',
                    'cp.peca_id as peca_id',
                    'p.nome as peca_nome',
                    'p.pn as peca_pn',
                    'cp.quantidade as quantidade',
                    'cp.valor as valor',
                ])
            ->order("p.nome")
            ->get();

        return $pecas;
    }



}

<?php

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

class Servico extends Model
{

    static $table = "servicos";

    static $primary = "id";

    public static function listByEquipamento($equipamento_id, $search=false)
    {

        $equipamento = Equipamento::findById($equipamento_id);

        $servicos = DB::table('produtos_servicos as ps')
            ->join('servicos as s', 'ps.servico_id', 's.id')
            ->where('ps.produto_id', $equipamento->produto_id);

        if ($search)
            $servicos = $servicos->like("nome", $search);

        $servicos = $servicos->select('s.*')->get();

        return $servicos;
    }

    public static function listByChamado($chamado_id, $baixado=0)
    {

        $servicos = DB::table('chamados_servicos as cs')
            ->join('servicos as s', 'cs.servico_id', 's.id')
            ->where('cs.chamado_id', $chamado_id);

        if (!is_null($baixado))
            $servicos = $servicos->where("cs.baixado", $baixado);

        $servicos = $servicos->select(['cs.id as id', 'cs.servico_id as servico_id', 's.nome as servico_nome', 'cs.quantidade as quantidade', 'cs.valor as valor'])
            ->order("s.nome")
            ->get();

        return $servicos;
    }


}

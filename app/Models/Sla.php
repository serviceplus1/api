<?php

namespace App\Models;

use App\Core\DB;
use App\Core\Model;
use App\Core\SLA as _SLA;
use App\Models\Cliente;

class Sla extends Model
{

    static $table = "sla";

    static $primary = "id";

    public static function trigger($chamado_id, $status, $solucao=null)
    {

        // Pegar dados do Chamado
        $chamado = Chamado::findById($chamado_id);

        if ($chamado->tipo_id==2) {

            // Matar SLA
            if (in_array($status, [3,4,6,7])) {

                // Pegar SLA Atual
                $sla_atual = DB::table("sla")
                    ->where("chamado_id", $chamado_id)
                    ->isNotNull("inicio")
                    ->isNull("final")
                    ->get();

                if (nreg($sla_atual)>0) {

                    $sla_atual = $sla_atual[0];

                    // Se status for de finalizar, pegar a data e hora informada
                    $data_final = !is_null($solucao) ? $solucao : date("Y-m-d H:i:s");

                    // Calcular SLA de acordo com o contrato do cliente
                    $cliente = Cliente::findById($chamado->cliente_id);
                    $util = $cliente->hora_id==1 ? true : false;

                    // Calcular a duração do sla
                    $duracao = _SLA::calculate($sla_atual->inicio, $data_final, $util);

                    // Atualizar registro SLA atual
                    DB::table("sla")->where("id", $sla_atual->id)
                        ->update([
                            "final" => $data_final,
                            "duracao" => $duracao
                        ]);
                }
            }
            if (in_array($status, [1,2,5])) {

                // Pegar SLA Atual
                $sla_atual = DB::table("sla")
                    ->where("chamado_id", $chamado_id)
                    ->isNotNull("inicio")
                    ->isNull("final")
                    ->get();

                if (nreg($sla_atual)==0) {

                    // Se já não houver um registro rodando, criar um novo
                    DB::table("sla")->insert(["chamado_id"=>$chamado_id, "inicio"=>date("Y-m-d H:i:s")]);
                }
            }

        }
    }

    public static function slaTotal($chamado_id)
    {

        $total = DB::table("sla")
            ->where("chamado_id", $chamado_id)
            ->isNotNull("duracao")
            ->sum("duracao");

        return $total;
    }


}

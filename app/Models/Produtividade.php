<?php

namespace App\Models;

use App\Core\Date;
use App\Core\DB;
use App\Core\Model;

class Produtividade extends Model
{

    static $table = "produtividade";

    static $primary = "id";

    public static function trigger($tecnico_id, $acao, $observacoes=null)
    {

        $now = Date::create()->format("Y-m-d H:i:s");

        // Pegar Registro Atual
        $atual = DB::table("produtividade")
            ->where("tecnico_id", $tecnico_id)
            ->isNotNull("inicio")
            ->isNull("final")
            ->get();

        if ($acao=="finalizar") {

            // Se existir um registro já iniciado e não finalizado
            if (nreg($atual)>0) {

                $atual = $atual[0];

                // Pegar intervalo em segundos
                $duracao = Date::diffInSeconds($atual->inicio);

                // Atualizar registro atual
                DB::table("produtividade")->where("id", $atual->id)
                    ->update(
                        [
                            "final" => $now,
                            "duracao" => $duracao,
                            "final_observacao" => $observacoes,
                        ]
                    );
            }
        }

        if ($acao=="iniciar") {

            if (nreg($atual)==0) {

                // Se já não houver um registro aberto, criar um novo
                DB::table("produtividade")->insert(
                    [
                                "tecnico_id" => $tecnico_id,
                                "inicio" => $now,
                                "inicio_observacao" => $observacoes
                            ]
                );
            }
        }

    }

    public static function duracaoTotal($tecnico_id)
    {

        $total = DB::table("produtividade")
            ->where("tecnico_id", $tecnico_id)
            ->isNotNull("duracao")
            ->sum("duracao");

        return $total;
    }


}

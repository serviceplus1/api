<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Model;

class ChamadoArquivo extends Model
{

    static $table = "chamados_arquivos";

    static $primary = "chamados_arquivos_id";

    public static function listByChamado($chamado_id)
    {

        $url = URL_APP."/storage/media/".TOKEN_CLIENT."/chamados/";

        $fields = [
            "chamados_arquivos_id",
            "chamados_arquivos_chamado",
            "chamados_arquivos_data",
            "chamados_arquivos_nome",
            "CONCAT('".$url."', chamados_arquivos_arquivo) as chamados_arquivos_arquivo",
            "chamados_arquivos_tipo",
            "chamados_arquivos_usuario"
        ];


        $arquivos = DB::table('chamados_arquivos as ca')
                    ->where('ca.chamados_arquivos_chamado', $chamado_id)
                    ->select($fields)
                    ->get();

        return $arquivos;
    }

}

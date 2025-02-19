<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Model;

class ProdutoFoto extends Model
{

    static $table = "produtos_fotos";

    static $primary = "foto_id";

    public static function main($produto_id)
    {

        $foto = DB::table(self::$table)
                ->select('arquivo')
                ->where('produto_id', $produto_id)
                ->first();

        if ($foto) {

            return $foto->arquivo;
        }
        else {

            return "no-photo.png";
        }
    }

}

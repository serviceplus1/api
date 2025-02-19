<?php

namespace App\Models;

use App\Core\Model;

class Instalacao extends Model
{

    static $table = "instalacao";

    static $primary = "id";

    static $uppers = ["equipamento", "marca", "modelo", "descricao"];
    static $required = ["equipamento", "marca", "modelo", "descricao"];

}

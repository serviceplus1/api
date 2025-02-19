<?php

namespace App\Models;

use App\Core\Model;

class Avaliacao extends Model
{

    static $table = "avaliacao";
    static $alias = "a";
    static $primary = "id";
    static $required = ["nota_chamado", "nota_tecnico"];

}

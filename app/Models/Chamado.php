<?php

namespace App\Models;

use App\Core\Model;

class Chamado extends Model
{

    static $table = "chamados";

    static $alias = "ch";

    static $primary = "chamado_id";

}

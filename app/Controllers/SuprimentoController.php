<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Data;
use App\Models\Suprimento;

class SuprimentoController extends Controller
{

    public function __construct($router)
    {
        parent::__construct($router);

        $this->auth = new Auth();

        if (parent::loggedApi()===false)
            unauthorized();

    }

    public function list($request)
    {

        $data = new Data($request);

        $data->required(["maquina_id"]);
        $data->is_int(["maquina_id"]);

        $suprimentos = Suprimento::listByEquipamento($data->maquina_id);

        return response_json($suprimentos);

    }

}

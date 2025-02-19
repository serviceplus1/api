<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\ChamadoCategoria;

class ChamadoCategoriaController extends Controller
{

    public function __construct($router)
    {
        parent::__construct($router);

        $this->auth = new Auth("tecnicos");

        if (parent::loggedApi()===false)
            unauthorized();

        $this->auth->checkGuard("tecnicos");
    }

    public function list()
    {

        $status = ChamadoCategoria::get();

        return response_json($status, 200);

    }

}

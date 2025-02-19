<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\ChamadoStatus;

class ChamadoStatusController extends Controller
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

        $ids = [1,8,9,10,7,5,2,6,3,4];

        $status = ChamadoStatus::orderByField("status_id", $ids)->get();

        return response_json($status, 200);

    }

}

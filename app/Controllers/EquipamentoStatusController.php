<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\EquipamentoStatus;

class EquipamentoStatusController extends Controller
{

    public function __construct($router)
    {
        parent::__construct($router);

        $this->auth = new Auth();

        if (parent::loggedApi()===false)
            unauthorized();
    }

    public function list()
    {
        $this->auth->checkGuard("tecnicos");

        $status = EquipamentoStatus::all();

        return response_json($status, 200, false);
    }

}

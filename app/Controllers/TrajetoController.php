<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Produtividade;

class TrajetoController extends Controller
{

    public function __construct($router)
    {
        parent::__construct($router);

        $this->auth = new Auth("tecnicos");

        if (parent::loggedApi()===false)
            unauthorized();

        $this->auth->checkGuard("tecnicos");
    }

    public function start()
    {
        $obs = "Trajeto Avulso Iniciado";
        Produtividade::trigger($this->auth->user()->tecnico_id, "iniciar", $obs);
    }

    public function finish()
    {
        $obs = "Trajeto Avulso Finalizado";
        Produtividade::trigger($this->auth->user()->tecnico_id, "finalizar", $obs);
    }

}

<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Data;
use App\Models\Chamado;
use App\Models\Historico;

class HistoricoController extends Controller
{

    public function __construct($router)
    {
        parent::__construct($router);

        $this->auth = new Auth("tecnicos");

        if (parent::loggedApi()===false)
            unauthorized();

        $this->auth->checkGuard("tecnicos");
    }

    public function list($request)
    {

        $data = new Data($_GET);
        $data->required(["chamado_id"]);
        $data->is_int(["chamado_id"]);

        $id = $data->id("chamado_id");

        $chamado = Chamado::findById($id);

        if (!$chamado)
            return response_json(["error"=>"Chamado não encontrado"], 400);

        $historico = Historico::where("chamado_id", $id);

        if ($data->has("tecnico")&&$data->tecnico==1)
            $historico = $historico->where("tecnico", 1);

        $order = $data->has("order") ? $data->order : "asc";

        $historico = $historico->order("data", $order)->get();

        foreach ($historico as $h) {

            $h->icone = '<i class="'.Historico::actions($h->tipo, "icon").'"></i>';
            $h->classe = Historico::actions($h->tipo, "class");
        }

        return response_json($historico);
    }

}
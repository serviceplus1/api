<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Data;
use App\Models\Rota;

class RotaController extends Controller
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

        $data = new Data($_REQUEST);

        $data->is_int("chamado_id");

        $id_tecnico = $this->auth->auth()->uid;

        $rotas = Rota::where("tecnico_id", $id_tecnico);

        if ($data->has("chamado_id")) {

            $rotas = $rotas->where("chamado_id", $data->chamado_id);
        }

        $rotas = $rotas->order("data", "DESC")->get();

        return response_json($rotas);
    }

    public function insert($request)
    {

        $data = new Data();
        $data->json();

        // "coordenadas":"-21.1409485,-48.9793204",
        // "uuid":"bac0e3b1-7cc4-4afd-8960-7a8c866632c7",
        // "grupo":"6f47aa0f-d20f-447e-bbda-58dc745f1334",
        // "descricao":"Testando api",
        // "data":"2021-01-20 14:58"

        // $data->is_int(["chamado_id"]);

        $data->required(["coordenadas"]);
        $data->add("tecnico_id", $this->auth->auth()->uid);
        // $data->add("data", date('Y-m-d H:i:s'));

        $insert = Rota::create($data->all());

        if ($insert->result==false)
            return response_json(["error" => "Erro ao inserir rota"], 400);

        return response_json(['success'=>'Rota inserida com sucesso', 'id'=>$insert->id]);
    }

}

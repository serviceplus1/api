<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Data;
use App\Models\Forma;

class FormaController extends Controller
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
        $formas = Forma::orderBy('descricao')->get();
        return response_json($formas);
    }

    public function insert()
    {

        $data = new Data();
        $data->json();
        $data->required("descricao");
        $data->allow("descricao");

        $insert = Forma::create($data->all());

        if ($insert->result==false)
            return response_json(["error" => "Erro ao inserir forma de pagamento"], 400);
            // return response_json(["message" => "Erro ao inserir forma de pagamento"], 400);

        return response_json(['success'=>'Forma de pagamento inserida com sucesso', 'id'=>$insert->id]);
        // return response_json(['message'=>'Forma de pagamento inserida com sucesso', 'id'=>$insert->id]);
    }

}

<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Data;
use App\Models\Instalacao;

class InstalacaoController extends Controller
{

    public function __construct($router)
    {

        parent::__construct($router);

        $this->auth = new Auth("clientes");

        if (parent::loggedApi()===false)
            unauthorized();

        $this->auth->checkGuard("clientes");
    }

    public function insert()
    {
        $data = new Data;
        $data->json();
        $data->required( Instalacao::getRequired() );
        $data->add("cliente_id", $this->auth->user()->cliente_id);
        $data->add("status", "PENDENTE");

        $insert = Instalacao::create($data->all());

        if ($insert->result==false)
            return response_json(["error" => "Erro ao inserir instalação"], 400);
            // return response_json(["message" => "Erro ao inserir instalação"], 400);

        return response_json(['success'=>'Instalação inserida com sucesso', 'id'=>$insert->id]);
        // return response_json(['message'=>'Instalação inserida com sucesso', 'id'=>$insert->id]);

    }

}

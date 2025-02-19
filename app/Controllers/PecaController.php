<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Data;
use App\Models\Peca;

class PecaController extends Controller
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

        $search = $data->has("q") ? $data->q : false;

        if ($data->has("maquina_id")) {

            $pecas = Peca::listByEquipamento($data->maquina_id, $search);

        } else {

            if ($search) {

                $pecas = Peca::like("p.nome", $search)
                    ->like("p.pn", $search, "OR")
                    ->order("p.nome")
                    ->get();
            } else {

                $pecas = Peca::order("nome")->get();
            }
        }

        $PECAS = [];

        foreach ($pecas as $p) {

            $P["peca_id"] = $p->id;
            $P["peca_nome"] = $p->nome;
            $P["peca_valor"] = $p->valor;
            $P["peca_pn"] = $p->pn;

            $PECAS[] = $P;
        }

        return response_json([$PECAS]);
    }

    public function insert($request)
    {

        $data = new Data();
        $data->json();

        $data->required(["nome"]);
        $data->add("verificado", 0);

        $insert = Peca::create($data->all());

        if ($insert->result==false)
            return response_json(["error" => "Erro ao inserir peça"], 400);

        return response_json(['success'=>'Peça inserida com sucesso', 'id'=>$insert->id]);
    }

}
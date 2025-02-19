<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Data;
use App\Models\Servico;

class ServicoController extends Controller
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

            $servicos = Servico::listByEquipamento($data->maquina_id, $search);

        } else {

            if ($search) {

                $servicos = Servico::like("nome", $search)->order("nome")->get();

            } else {

                $servicos = Servico::order("nome")->get();
            }

        }

        $SERVICOS = [];

        foreach ($servicos as $s) {

            $S["servico_id"] = (int)$s->id;
            $S["servico_nome"] = $s->nome;
            $S["servico_valor"] = $s->valor;

            $SERVICOS[] = $S;
        }

        return response_json([$SERVICOS]);

    }

}
<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Data;
use App\Models\Ponto;
use Exception;

class PontoController extends Controller
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

        $data = new Data($_GET);
        $data->is_date(["data"]);
        $data->list("tipo", ["entrada","saida"]);
        $data->list("order", ["data-asc","data-desc","id-asc","id-desc"]);
        $data->date(["data"]);

        $fields = [
            'id AS id_ponto',
            'data AS data',
            'tipo AS tipo',
            'descricao AS descricao',
            'observacoes AS observacoes',
            'uuid AS uuid',
        ];

        $pontos = Ponto::select($fields);

        $pontos = $pontos->where('tecnico_id', $this->auth->auth()->uid);

        if ($data->has('tipo')) {

            $pontos = $pontos->where("tipo", $data->tipo);
        }


        /* teste */
        $data->add("data_inicial", date("Y-m-01"));
        $data->add("data_final", date("Y-m-t"));

        if ($data->has('data_inicial') || $data->has('data_final')) {

            if ($data->has('data_inicial') && $data->has('data_final')) {

                $pontos = $pontos->between("DATE(data)", $data->data_inicial, $data->data_final);

            }
            else if ($data->has('data_inicial')) {

                $pontos = $pontos->where("DATE(data)", $data->data_inicial, ">=");

            }
            else if ($data->has('data_final')) {

                $pontos = $pontos->where("DATE(data)", $data->data_final, "<=");
            }
        }

        /**/

        if ($data->has('data')) {

            $pontos = $pontos->where("DATE(data)", $data->data);
        }

        if ($data->has('order')) {

            if ($data->order=='data-asc')       $pontos = $pontos->orderBy('data');
            elseif ($data->order=='data-desc')  $pontos = $pontos->orderBy('data', 'DESC');
            elseif ($data->order=='id-asc')     $pontos = $pontos->orderBy('id');
            elseif ($data->order=='id-desc')    $pontos = $pontos->orderBy('id', 'DESC');

        }
        else {

            $pontos = $pontos->orderBy('data', 'DESC');
        }

        $pontos = $pontos->get();

        $this->log->app("Lista de pontos", [
            "request"=> $_REQUEST,
            "body"=> $data->all(),
        ]);

        return response_json($pontos);

    }

    public function insert($request)
    {

        $data = new Data();
        $data->json();

        $data->required(["tipo"]);
        $data->list("tipo", ["entrada","saida"]);
        $data->max(["descricao"], 255);

        $data->add("data", date("Y-m-d H:i:s"));
        $data->add("tecnico_id", $this->auth->auth()->uid);

        try {

            $insert = Ponto::firstOrCreate($data->all());

        } catch (Exception $e) {

            $this->log->exception($e);
        }

        if ($insert->result) {

            $this->log->info("Ponto Inserido com Sucesso", [
                "request"=> $_REQUEST,
                "body"=> $data->all(),
            ]);

            return response_json(['success'=>'Ponto inserido com sucesso', 'id'=>$insert->id]);

        }
        else if (isset($insert->result)&&$insert->result==false) {

            $this->log->erro("Erro ao inserir ponto", [
                "request"=> $_REQUEST,
                "body"=> $data->all(),
            ]);

            return response_json(["error" => "Erro ao inserir ponto"], 400);
        }
    }
}
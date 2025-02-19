<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Data;
use App\Core\DB;
use App\Models\Avaliacao;

class AvaliacaoController extends Controller
{

    public function __construct($router)
    {

        parent::__construct($router);

        $this->auth = new Auth("clientes");

        if (parent::loggedApi()===false)
            unauthorized();

        $this->guard = $this->auth->auth()->guard;

    }

    /**
     * Listar avaliações do técnico
     *
     * @return void
     */
    public function list()
    {

        $data = new Data($_GET);
        $data->remove(["route"]);
        $data->is_bool(["status"]);
        $data->min(["limit"], 1);
        $data->list("order", ["data-asc","data-desc"]);

        $fields = [
            'a.id AS id',
            'a.cliente_id AS cliente_id',
            'a.chamado_id AS chamado_id',
            'a.tecnico_id AS tecnico_id',
            'a.nota_chamado AS nota_chamado',
            'a.nota_tecnico AS nota_tecnico',
            'a.observacoes AS observacoes',
            'a.data AS data',
            'a.status AS status',
        ];

        if ($this->guard=="clientes") {
            $cliente_id = $this->auth->user()->cliente_id;
        }

        $avaliacoes = Avaliacao::join('clientes as c', 'a.cliente_id', 'c.cliente_id')
            ->leftJoin("tecnicos as t", "a.tecnico_id", "t.tecnico_id")
            ->leftJoin('chamados as ch', 'a.chamado_id', 'ch.chamado_id')
            ->select($fields);

        $params = [];

        if ($data->has("cliente_id")) {

            $avaliacoes = $avaliacoes->where('a.cliente_id', $data->cliente_id);
            $params["cliente_id"] = $data->cliente_id;
        }
        elseif ($this->guard == 'clientes') {

            $avaliacoes = $avaliacoes->where('a.cliente_id', $cliente_id);
        }

        if ($data->has("tecnico_id")) {

            $avaliacoes = $avaliacoes->where('a.tecnico_id', $data->tecnico_id);
            $params["tecnico_id"] = $data->tecnico_id;
        }
        elseif ($this->guard == 'tecnicos') {

            $avaliacoes = $avaliacoes->where('a.tecnico_id', $this->auth->auth()->uid);
        }

        if ($data->has("chamado_id")) {

            $avaliacoes = $avaliacoes->where('a.chamado_id', $data->chamado_id);
            $params["chamado_id"] = $data->chamado_id;
        }

        if ($data->has('status')) {

            $avaliacoes = $avaliacoes->where("status", $data->status);
            $params["status"] = $data->status;
        }

        if ($data->has('order')) {

            if ($data->order=='data-asc')       $avaliacoes = $avaliacoes->orderBy('data');
            elseif ($data->order=='data-desc')  $avaliacoes = $avaliacoes->orderBy('data', 'desc');

            $params["order"] = $data->order;
        }

        if ($data->has('limit')) {

            $params["limit"] = $data->limit;

            $page = $data->has('page') ? $data->page : 1;

            $offset = ($page * $data->limit) - $data->limit;

            $total = count($avaliacoes->get(false));

            $last_page = ceil($total / $data->limit);

            $next_page = $page+1 > $last_page ? null : $page+1;

            $prev_page = $page-1 == 0 ? null : $page-1;

            $dados = $avaliacoes->limit($data->limit, $offset)->get();

            $response["current_page"] = $page;
            $response["data"] = $dados;
            $response["first_page_url"] = $this->router->route("api.avaliacao.list")."?".http_build_query($params)."&page=1";
            $response["from"] = $offset+1;
            $response["last_page"] = $last_page;
            $response["last_page_url"] = $this->router->route("api.avaliacao.list")."?".http_build_query($params)."&page=".$last_page;
            $response["next_page_url"] = $next_page==null ? null : $this->router->route("api.avaliacao.list")."?".http_build_query($params)."&page=".$next_page;
            $response["path"] = $this->router->route("api.avaliacao.list");
            $response["per_page"] = $data->limit;
            $response["prev_page_url"] = $prev_page==null ? null : $this->router->route("api.avaliacao.list")."?".http_build_query($params)."&page=".$prev_page;
            $response["to"] = $offset+$data->limit;
            $response["total"] = $total;

        } else {

            $response = $avaliacoes->get();
        }

        return response_json($response);
    }

    /**
     * Mostrar avaliação do técnico
     *
     * @param array $request
     * @return void
     */
    public function show($request)
    {

        $data = new Data($request);
        $data->required(["id"]);
        $data->is_int(["id"]);
        $id = $data->id();

        $fields = [
            'a.id AS id',
            'a.cliente_id AS cliente_id',
            'a.chamado_id AS chamado_id',
            'a.tecnico_id AS tecnico_id',
            't.tecnico_nome AS tecnico_nome',
            'a.nota_chamado AS nota_chamado',
            'a.nota_tecnico AS nota_tecnico',
            'a.observacoes AS observacoes',
            'a.data AS data',
            'a.status AS status',
        ];

        $avaliacao =  Avaliacao::join('clientes as c', 'a.cliente_id', 'c.cliente_id')
            ->leftJoin('tecnicos as t', 'a.tecnico_id', 't.tecnico_id')
            ->leftJoin('chamados as ch', 'a.chamado_id', 'ch.chamado_id')
            ->select($fields)
            ->where('a.id', $id)
            ->first();

        if (!$avaliacao)
            return response_json(['error'=>'Avaliação não encontrada'], 400);
            // return response_json(['message'=>'Avaliação não encontrada'], 400);


        if ($this->auth->auth()->guard == 'clientes') {

            if ($avaliacao->cliente_id != $this->auth->user()->cliente_id) {

                return response_json(['error'=>'Avaliação não pertence ao cliente'], 400);
                // return response_json(['message'=>'Avaliação não pertence ao cliente'], 400);
            }
        }

        return response_json($avaliacao, 200);

    }

    public function update($request)
    {

        $request = new Data($request);
        $request->required(["id"]);
        $request->is_int(["id"]);

        $id = $request->id();

        $data = new Data();
        $data->json();
        $data->required(Avaliacao::getRequired());
        $data->datetime(["data"], true);

        $avaliacao = Avaliacao::find($id);

        if ($avaliacao->cliente_id != $this->auth->user()->cliente_id)
            return response_json(['error'=>'Avaliação não pertence ao cliente'], 400);
            // return response_json(['message'=>'Avaliação não pertence ao cliente'], 400);

        if ($avaliacao->nota_chamado||$avaliacao->nota_tecnico)
            return response_json(['error'=>'Avaliação já registrada'], 400);
            // return response_json(['message'=>'Avaliação já registrada'], 400);

        $update = Avaliacao::update($id, $data->all());

        if ($update==false)
            return response_json(["error" => "Erro ao atualizar avaliação"], 400);
            // return response_json(["message" => "Erro ao atualizar avaliação"], 400);

        return response_json(['success'=>'Avaliação finalizada com sucesso']);
        // return response_json(['message'=>'Avaliação finalizada com sucesso']);

    }



}

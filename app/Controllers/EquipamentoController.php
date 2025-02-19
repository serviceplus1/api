<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Data;
use App\Models\Equipamento;

class EquipamentoController extends Controller
{

    private $guard;

    public function __construct($router)
    {

        parent::__construct($router);

        $this->auth = new Auth();

        if (parent::loggedApi()===false)
            unauthorized();

        $this->guard = $this->auth->auth()->guard;
    }


    public function list()
    {

        $data = new Data($_GET);

        $data->remove(["route"]);
        $data->is_int(["cliente_id", "limit"]);
        $data->is_bool(["abertura"]);
        $data->min(["limit"], 1);

        $user = $this->auth->user();

        // 'e.numero AS maquina_numero',

        $fields = [
            'e.maquina_id AS maquina_id',
            'e.cliente_id AS cliente_id',
            'cl.nome AS cliente_nome',
            'cl.contato AS cliente_contato',
            'e.local AS maquina_local',
            'CAST(e.numero AS CHAR) AS maquina_numero',
            'e.status AS maquina_status',
            's.descricao AS status_descricao',
            's.cor AS status_cor',
            's.abertura AS status_abertura',
            'p.produto_id AS produto_id',
            'p.nome AS produto_nome',
            'p.cor AS produto_cor',
            'p.contador_mono AS produto_contador_mono',
            'p.contador_color AS produto_contador_color',
            'c.categoria_nome AS produto_categoria',
            'm.marca_nome AS produto_marca',
            'CONCAT("https://app.serviceplus.com.br/storage/media/'.TOKEN_CLIENT.'/produtos/", f.arquivo) as produto_foto',
        ];

        $equipamentos  = Equipamento::join('clientes AS cl', 'e.cliente_id', 'cl.cliente_id')
                                    ->leftJoin('equipamentos_status AS s', 'e.status', 's.id')
                                    ->join('produtos AS p', 'e.produto_id', 'p.produto_id')
                                    ->leftJoin('produtos_fotos AS f', 'f.produto_id', 'p.produto_id')
                                    ->join('categorias AS c', 'p.categoria_id', 'c.categoria_id')
                                    ->join('marcas AS m', 'p.marca_id', 'm.marca_id')
                                    ->select($fields);

        if ($this->guard == 'clientes') {

            $equipamentos = $equipamentos->where('e.cliente_id', $user->cliente_id);
        }
        elseif ($data->has('cliente_id')) {

            $equipamentos = $equipamentos->where('e.cliente_id', $data->cliente_id);
        }

        if ($data->has('numero_serie')) {

            $equipamentos = $equipamentos->where('e.numero', $data->numero_serie);
        }

        if ($data->has('q')) {

            $equipamentos = $equipamentos->agroup()
                                         ->like("cl.nome", $data->q, null)
                                         ->like("p.nome", $data->q, "OR")
                                         ->like("m.marca_nome", $data->q, "OR")
                                         ->endAgroup();
        }

        if ($data->has('abertura'))
            $equipamentos = $equipamentos->where('s.abertura', $data->abertura);

        if ($data->has('limit')) {

            $params["limit"] = $data->limit;

            $page = $data->has('page') ? $data->page : 1;

            $offset = ($page * $data->limit) - $data->limit;

            $total = count($equipamentos->get(false));

            $last_page = ceil($total / $data->limit);

            $next_page = $page+1 > $last_page ? null : $page+1;

            $prev_page = $page-1 == 0 ? null : $page-1;

            $dados = $equipamentos->limit($data->limit, $offset)->get();

            $response["current_page"] = $page;
            $response["data"] = $dados;
            $response["first_page_url"] = $this->router->route("api.equipamento.list")."?".http_build_query($params)."&page=1";
            $response["from"] = $offset+1;
            $response["last_page"] = $last_page;
            $response["last_page_url"] = $this->router->route("api.equipamento.list")."?".http_build_query($params)."&page=".$last_page;
            $response["next_page_url"] = $next_page==null ? null : $this->router->route("api.equipamento.list")."?".http_build_query($params)."&page=".$next_page;
            $response["path"] = $this->router->route("api.equipamento.list");
            $response["per_page"] = $data->limit;
            $response["prev_page_url"] = $prev_page==null ? null : $this->router->route("api.equipamento.list")."?".http_build_query($params)."&page=".$prev_page;
            $response["to"] = $offset+$data->limit;
            $response["total"] = $total;

        } else {

            $dados = $equipamentos->get();

            foreach ($dados as $m) {

                $m->maquina_id = (int)$m->maquina_id;
                $m->cliente_id = (int)$m->cliente_id;
                $m->maquina_status  = (int)$m->maquina_numero;
                $m->status_abertura = (int)$m->status_abertura;
                $m->produto_contador_mono = (int)$m->produto_contador_mono;
                $m->produto_contador_color = (int)$m->produto_contador_color;
                $m->produto_id = (int)$m->produto_id;
            }

            $response = $dados;
        }

        return response_json($response, 200, "utf-8", JSON_BIGINT_AS_STRING);
    }


    public function show()
    {

        $data = new Data($_GET);
        $data->is_int(["maquina_id"]);
        $data->remove("route");

        if (empty($data->all())) {

            return response_json(['error'=>'Número de série ou ID do Equipamento inexistente'], 400);
            // return response_json(['message'=>'Número de série ou ID do Equipamento inexistente'], 400);
        }

        if ($data->has('maquina_id')) {

            $equipamento = Equipamento::findById($data->maquina_id);
        }
        elseif ($data->has('numero_serie')) {

            $equipamento = Equipamento::findBySerialNumber($data->numero_serie);
            $cont = isset($equipamento) ? count((array)$equipamento) : 0;
            $equipamento = $equipamento[0];
        }

        if (isset($equipamento)) {

            if (isset($cont)&&$cont>1) {

                return response_json(['error'=>'Equipamento em duplicidade'], 400);
                // return response_json(['message'=>'Equipamento em duplicidade'], 400);
            }
        } else {

            return response_json(['error'=>'Equipamento não encontrado'], 400);
            // return response_json(['message'=>'Equipamento não encontrado'], 400);
        }

        if ($this->guard == 'clientes') {

            if ($equipamento->cliente_id != $this->auth->user()->cliente_id) {

                return response_json(['error'=>'Equipamento não pertence ao cliente'], 400);
                // return response_json(['message'=>'Equipamento não pertence ao cliente'], 400);
            }
        }

        $equipamento->maquina_numero = (string)$equipamento->maquina_numero;

        return response_json($equipamento);
    }

}
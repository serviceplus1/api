<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Data;
use App\Core\Jwt;
use App\Models\Cliente;
use App\Models\ClienteUsuario;
use App\Src\Here;

class ClienteController extends Controller
{

    public function __construct($router)
    {
        parent::__construct($router);

        $this->auth = new Auth();

        if (parent::loggedApi()===false)
            unauthorized();

    }

    public function me()
    {
        $this->auth->checkGuard("clientes");

        // "cliente_id": 166,
        // "matriz_id": 3,
        // "hora_id": 2,
        // "hora_limite": 48,
        // "codigo": "01",
        // "pessoa": "J",
        // "razao": "Coopercitrus - Auditoria Interna",
        // "nome": "Coopercitrus - Auditoria Interna",
        // "documento": null,
        // "empresa": "inkajet",
        // "contato": "Vinicius",
        // "endereco": "Av. Quito Stamato",
        // "numero": "530",
        // "complemento": "",
        // "bairro": "Centro",
        // "cidade": "Bebedouro",
        // "estado": "SP",
        // "cep": "14.700-000",
        // "telefone": "(17) 3344-3262",
        // "login": "coopercitrusaud",
        // "email": "vinicius.auditoria@coopercitrus.com.br",
        // "nomeSupervisor": "Carlos Zamariolo",
        // "emailSupervisor": "carlos.zamariolo@coopercitrus.com.br",
        // "status": 0,
        // "status_old": "ativado",
        // "contador": "atualizado",
        // "coordenadas": "-20.9496743,-48.4795696",
        // "suprimento": 1,
        // "notificacao_id": null,
        // "client_token": "27ddbb674d3701f4e0f8b26f9f183b0e6c9ca145",
        // "enviar_email": 0,
        // "insert_at": null

        $user = $this->auth->user();
        unset($user->password);


        return response_json($user);
    }

    public function logout()
    {
        return response_json(['message' => 'Você foi desconectado']);
    }

    public function refresh()
    {
        $this->auth->checkGuard("clientes");

        $auth = $this->auth->auth();

        // Criar JWT Token
        $token = Jwt::generate(["guard"=>"clientes", "uid"=>$auth->uid]);

        if ($token) {

            $response["access_token"] = $token;
            $response["token_type"] = "bearer";
            $response["expires_in"] = Jwt::decode($token)->exp;

            return response_json($response);

        } else {

            return response_json(["error"=>"Houve um erro ao criar as credenciais"], 401);
            // return response_json(["message"=>"Houve um erro ao criar as credenciais"], 401);
        }
    }

    public function list()
    {

        $this->auth->checkGuard("tecnicos");

        $data = new Data($_GET);
        $data->remove(["route"]);
        $data->is_bool(["status"]);
        $data->list("order", ["nome-asc","nome-desc","razao-asc","razao-desc"]);
        $data->min(["limit"], 1);

        $fields = [
            "cliente_id",
            "matriz_id",
            "hora_id",
            "hora_limite",
            "codigo",
            "pessoa",
            "razao",
            "nome",
            "documento",
            "empresa",
            "contato",
            "endereco",
            "numero",
            "complemento",
            "bairro",
            "cidade",
            "estado",
            "cep",
            "telefone",
            "login",
            "email",
            // "nomeSupervisor",
            // "emailSupervisor",
            "status",
            "coordenadas",
            "suprimento",
            "client_token",
        ];

        $clientes = Cliente::select($fields);

        $params = [];

        if ($data->has('q')) {

            $clientes = $clientes->where("cliente_id", 0, ">")
                            ->agroup()
                            ->like("razao", $data->q, null)
                            ->like("nome", $data->q, "OR")
                            ->like("cidade", $data->q, "OR")
                            ->endAgroup();

            $params["q"] = $data->q;
        }

        if ($data->has('status')) {

            $clientes = $clientes->where("status", $data->status);
            $params["status"] = $data->status;
        }

        if ($data->has('order')) {

            if ($data->order=='nome-asc')       $clientes = $clientes->orderBy('nome');
            elseif ($data->order=='nome-desc')  $clientes = $clientes->orderBy('nome', 'desc');
            elseif ($data->order=='razao-asc')  $clientes = $clientes->orderBy('razao');
            elseif ($data->order=='razao-desc') $clientes = $clientes->orderBy('razao', 'desc');

            $params["order"] = $data->order;
        }

        if ($data->has('limit')) {

            $params["limit"] = $data->limit;

            $page = $data->has('page') ? $data->page : 1;

            $offset = ($page * $data->limit) - $data->limit;

            $total = count($clientes->get(false));

            $last_page = ceil($total / $data->limit);

            $next_page = $page+1 > $last_page ? null : $page+1;

            $prev_page = $page-1 == 0 ? null : $page-1;

            $dados = $clientes->limit($data->limit, $offset)->get();


            $uri = $_SERVER["SCRIPT_URI"];

            // $url_query = http_build_query($params)."&";
            $url_query = "";

            $response["current_page"] = $page;
            $response["data"] = $dados;
            $response["first_page_url"] = $uri."?".$url_query."page=1";
            $response["from"] = $offset+1;
            $response["last_page"] = $last_page;
            $response["last_page_url"] = $uri."?".$url_query."page=".$last_page;
            $response["next_page_url"] = $next_page==null ? null : $uri."?".$url_query."page=".$next_page;
            $response["path"] = $uri;
            $response["per_page"] = $data->limit;
            $response["prev_page_url"] = $prev_page==null ? null : $uri."?".$url_query."page=".$prev_page;
            $response["to"] = $offset+$data->limit;
            $response["total"] = $total;

        } else {

            $response = $clientes->get();
        }

        return response_json($response);

    }

    public function create()
    {

        $data = new Data();
        $data->json();
        $data->required(["razao", "cidade", "estado"]);
        $data->is_email(["email", "emailSupervisor"]);

        if ($data->has("documento")) {

            $cliente = Cliente::where("documento", $data->documento)->first();

            if (isset($cliente))
                return response_json(["error" => "Cliente já existente. Id: ".$cliente->cliente_id], 400);
        }

        $data->add("matriz_id", 0);
        $data->add("hora_id", 2);
        $data->add("hora_limite", 48);
        $data->add("pessoa", $data->has("pessoa") ? $data->pessoa : "J");
        $data->add("status", 1);

        // Encontrar Coordenadas
        if ($data->has("cidade")&&$data->has("estado")) {

            $endereco  = remove_acentos($data->endereco);
            $endereco .= " ".remove_acentos($data->numero);
            $endereco .= " ".remove_acentos($data->bairro);
            $endereco .= " ".remove_acentos($data->cidade);
            $endereco .= " ".remove_acentos($data->estado);

            $Here = new Here();
            $coordenadas = $Here->coordinates(urlencode($endereco));

            if ($coordenadas)
                $data->add("coordenadas", $coordenadas->Latitude.",".$coordenadas->Longitude);
        }

        $cliente = Cliente::first();

        $data->add("suprimento", $cliente->suprimento);
        $data->add("client_token", $cliente->client_token);

        $insert = Cliente::create($data->all());

        if ($insert->result==false)
            return response_json(["error" => "Erro ao inserir cliente"], 400);

        return response_json(['success'=>'Cliente inserido com sucesso', 'id'=>$insert->id]);

    }

    public function update()
    {
        $this->auth->checkGuard("clientes");

        $data = new Data();
        $data->json();
        $data->nullIfEmpty(["notificacao_id"]);

        $user_id = $this->auth->user()->id;

        if ($user_id) {

            if (!empty($data->all())) {

                $update = ClienteUsuario::where('id', $user_id)->update($data->all());

                if ($update) {

                    return response_json(['success' => 'Usuário alterado com sucesso'], 200);
                    // return response_json(['message' => 'Usuário alterado com sucesso'], 200);

                } else {

                    return response_json(['error'=>'Erro ao alterar técnico'], 400);
                    // return response_json(['message'=>'Erro ao alterar técnico'], 400);
                }

            } else {

                return response_json(['error'=>'Não foram passados dados para atualização'], 400);
                // return response_json(['message'=>'Usuário não encontrado'], 400);

            }

        } else {

            return response_json(['error'=>'Usuário não encontrado'], 400);
            // return response_json(['message'=>'Usuário não encontrado'], 400);

        }
    }





}

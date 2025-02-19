<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Data;
use App\Core\DB;
use App\Core\Email;
use App\Core\File;
use App\Core\FTP;
use App\Core\SLA;
use App\Models\Assinatura;
use App\Models\Avaliacao;
use App\Models\Chamado;
use App\Models\ChamadoArquivo;
use App\Models\ChamadoPeca;
use App\Models\ChamadoServico;
use App\Models\ChamadoSuprimento;
use App\Models\Cliente;
use App\Models\Configuracao;
use App\Models\Contagem;
use App\Models\Equipamento;
use App\Models\Historico;
use App\Models\Peca;
use App\Models\ProdutoFoto;
use App\Models\Servico;
use App\Models\Suprimento;
use App\Src\Here;
use App\Src\Onesignal;

class ChamadoController extends Controller
{

    protected $guard;

    public function __construct($router)
    {
        parent::__construct($router);

        $this->auth = new Auth();

        if (parent::loggedApi()===false)
            unauthorized();

        $this->guard = $this->auth->auth()->guard;

        $this->user = $this->auth->user();
    }

    public function show($request)
    {

        $data = new Data($request);
        $data->required(["id"]);
        $data->is_int(["id"]);

        $id = $data->id();

        $fields = [

            'ch.chamado_id AS chamado_id',
            'ch.data AS chamado_data',
            'ch.mono_un AS chamado_mono_un',
            'ch.color_un AS chamado_color_un',
            'ch.azul_un AS chamado_azul_un',
            'ch.magenta_un AS chamado_magenta_un',
            'ch.amarelo_un AS chamado_amarelo_un',
            'ch.preto_un AS chamado_preto_un',
            'ch.cilindro_un AS chamado_cilindro_un',
            'ch.anexo AS chamado_anexo',
            'ch.mensagem AS chamado_mensagem',
            'ch.data_inicio AS chamado_data_inicio',
            'ch.data_checkin AS chamado_data_checkin',
            'ch.data_solucao AS chamado_data_solucao',
            'ch.sla AS chamado_sla',
            'ch.problemas AS chamado_problemas',
            'ch.mensagem_solucao AS chamado_mensagem_solucao',
            'ch.local_solucao AS chamado_local_solucao',
            'ch.contagem AS chamado_contagem',
            'ch.assinatura AS chamado_assinatura',
            'ch.status_id AS status_id',
            'ch.contador_mono AS chamado_contador_mono',
            'ch.contador_color AS chamado_contador_color',
            's.status_nome AS status_nome',
            'ch.data_previsao AS chamado_data_previsao',
            'tp.tipo_id AS tipo_id',
            'tp.tipo_chamado AS tipo_nome',
            'ch.categoria_id AS categoria_id',
            'categ.descricao AS categoria_descricao',
            'categ.prefixo AS categoria_prefixo',
            'categ.cor AS categoria_cor',
            'categ.cor_texto AS categoria_cor_texto',
            'ch.tecnico_id AS tecnico_id',
            't.tecnico_nome AS tecnico_nome',
            't.localizacao AS tecnico_localizacao',
            'ch.cliente_id AS cliente_id',
            'cl.nome AS cliente_nome',
            'cl.contato AS cliente_contato',
            'cl.endereco AS cliente_endereco',
            'cl.numero AS cliente_numero',
            'cl.complemento AS cliente_complemento',
            'cl.bairro AS cliente_bairro',
            'cl.cidade AS cliente_cidade',
            'cl.estado AS cliente_estado',
            'cl.cep AS cliente_cep',
            'cl.telefone AS cliente_telefone',
            'cl.coordenadas AS cliente_coordenadas',
            'ch.maquina_id AS maquina_id',
            'e.local AS maquina_local',
            'e.numero AS maquina_numero',
            'e.operante AS maquina_operante',
            'p.produto_id AS produto_id',
            'p.nome AS produto_nome',
            'p.cor AS produto_cor',
            'p.contador_mono AS produto_contador_mono',
            'p.contador_color AS produto_contador_color',
            'c.categoria_nome AS produto_categoria',
            'm.marca_nome AS produto_marca',
            'null as produto_foto'
        ];

        $chamado =  Chamado::join('clientes AS cl', 'ch.cliente_id', 'cl.cliente_id')
            ->leftJoin('chamados_categorias AS categ', 'ch.categoria_id', 'categ.id')
            ->leftJoin('tecnicos AS t', 'ch.tecnico_id', 't.tecnico_id')
            ->leftJoin('clientes_equipamentos AS e', 'ch.maquina_id', 'e.maquina_id')
            ->leftJoin('produtos AS p', 'e.produto_id', 'p.produto_id')
            ->leftJoin('categorias AS c', 'p.categoria_id', 'c.categoria_id')
            ->leftJoin('marcas AS m', 'p.marca_id', 'm.marca_id')
            ->join('chamados_tipos AS tp', 'ch.tipo_id', 'tp.tipo_id')
            ->join('chamados_status AS s', 'ch.status_id', 's.status_id')
            ->select($fields)
            ->where('ch.chamado_id', $id)
            ->first();

        if (!$chamado)
            return response_json(['error'=>'Chamado não encontrado'], 400);
            // return response_json(['message'=>'Chamado não encontrado'], 400);

        if ($this->guard=="clientes" && $chamado->cliente_id != $this->user->cliente_id)
            return response_json(['error'=>'Você não tem permissão para visualizar o chamado'], 400);
            // return response_json(['message'=>'Você não tem permissão para visualizar o chamado'], 400);

        if ($chamado->maquina_id) {

            $foto = ProdutoFoto::main($chamado->produto_id);

            $chamado->produto_foto = URL_APP."/storage/media/".TOKEN_CLIENT."/produtos/".$foto;

            $chamado->maquina_numero = $chamado->maquina_numero;
        }

        if ($chamado->chamado_anexo) {

            $chamado->chamado_anexo = URL_APP."/storage/media/".TOKEN_CLIENT."/chamados/".$chamado->chamado_anexo;
        }

        if ($chamado->status_id==5 && !is_null($chamado->cliente_coordenadas) && !is_null($chamado->tecnico_localizacao)) {

            $Here = new Here();

            $trafficTime = $Here->summaryRoute($chamado->tecnico_localizacao, $chamado->cliente_coordenadas)->trafficTime;
            $chamado->chamado_tempo = time_left($trafficTime);

        } else {

            $chamado->chamado_tempo = null;
        }

        // Distancia da configuração
        $chamado->distancia = Configuracao::findById(1)->chamados_distancia_checkin;

        $chamado->pecas = Peca::listByChamado($chamado->chamado_id);

        $chamado->servicos = Servico::listByChamado($chamado->chamado_id);

        $chamado->suprimentos = Suprimento::listByChamado($chamado->chamado_id, null);

        $chamado->arquivos = ChamadoArquivo::listByChamado($chamado->chamado_id);

        return response_json($chamado);
    }

    public function list()
    {
        $data = new Data($_GET, false);

        $data->remove(["route"]);
        $data->is_int(["chamado_id", "tecnico_id", "tipo", "limit"]);
        $data->list("tipo", [1, 2]);
        $data->list("status", [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, "abertos"]);
        $data->list("order", ["data-desc", "data-asc", "id-desc", "id-asc"]);
        $data->min(["limit"], 1);
        $data->nullIfExists(["categoria_id"]);

        $fields = [
            'ch.chamado_id AS chamado_id',
            'ch.data AS chamado_data',
            'ch.data_previsao AS chamado_data_previsao',
            'ch.mensagem_solucao AS chamado_mensagem_solucao',
            'ch.data_solucao AS chamado_data_solucao',
            'ch.tipo_id AS tipo_id',
            'ct.tipo_chamado AS tipo_nome',
            'ch.categoria_id AS categoria_id',
            'categ.descricao AS categoria_descricao',
            'categ.prefixo AS categoria_prefixo',
            'categ.cor AS categoria_cor',
            'categ.cor_texto AS categoria_cor_texto',
            'ch.tecnico_id AS tecnico_id',
            't.tecnico_nome AS tecnico_nome',
            't.localizacao AS tecnico_localizacao',
            'ch.cliente_id AS cliente_id',
            'c.nome AS cliente_nome',
            'c.endereco AS cliente_endereco',
            'c.numero AS cliente_numero',
            'c.complemento AS cliente_complemento',
            'c.bairro AS cliente_bairro',
            'c.cidade AS cliente_cidade',
            'c.estado AS cliente_estado',
            'c.coordenadas AS cliente_coordenadas',
            'ch.cliente_id AS cliente_id',
            'ch.status_id AS status_id',
            'cs.status_nome AS status_nome',
            'ch.maquina_id AS maquina_id',
            'p.nome AS maquina_nome',
            'e.numero AS maquina_numero',
            'e.local AS maquina_local',
            'e.operante AS maquina_operante',
        ];


        $chamados =  Chamado::join('clientes as c', 'ch.cliente_id', 'c.cliente_id')
            ->leftJoin('chamados_categorias AS categ', 'ch.categoria_id', 'categ.id')
            ->leftJoin('clientes_equipamentos AS e', 'ch.maquina_id', 'e.maquina_id')
            ->leftJoin('produtos AS p', 'e.produto_id', 'p.produto_id')
            ->leftJoin('tecnicos AS t', 'ch.tecnico_id', 't.tecnico_id')
            ->leftJoin('categorias AS cat', 'p.categoria_id', 'cat.categoria_id')
            ->leftJoin('marcas AS m', 'p.marca_id', 'm.marca_id')
            ->join('chamados_tipos as ct', 'ch.tipo_id', 'ct.tipo_id')
            ->join('chamados_status as cs', 'ch.status_id', 'cs.status_id')
            ->select($fields)
            ->where('ch.chamado_id', 0, ">");

        if ($data->has("chamado_id")) {

            $chamados = $chamados->where('ch.chamado_id', $data->chamado_id);

        } else {

            if ($data->contain("categoria_id")) {

                if (is_null($data->categoria_id)) {

                    $chamados = $chamados->isNull('ch.categoria_id');

                } else {

                    $chamados = $chamados->where('ch.categoria_id', $data->categoria_id);
                }
                $params["categoria_id"] = $data->categoria_id;
            }

            if ($data->has("maquina_id")) {
                $chamados = $chamados->where('ch.maquina_id', $data->maquina_id);
                $params["maquina_id"] = $data->maquina_id;
            }

            if ($data->has("numero_serie")) {
                $chamados = $chamados->where('e.numero', $data->numero_serie);
                $params["numero_serie"] = $data->numero_serie;
            }

            if ($data->has('tipo')) {
                $chamados = $chamados->where('ch.tipo_id', $data->tipo);
                $params["tipo"] = $data->tipo;
            }

            if ($data->has('tecnico_id')) {
                $chamados = $chamados->where('ch.tecnico_id', $data->tecnico_id);
                $params["tecnico_id"] = $data->tecnico_id;
            }

            if ($data->has('status')) {

                if ($data->status=="abertos") {

                    $chamados = $chamados->where('cs.status_situacao', "aberto");
                    $params["status"] = "aberto";

                } else {

                    $chamados = $chamados->where('ch.status_id', $data->status);
                    $params["status"] = $data->status;
                }
            }

            if ($data->has('q')) {
                $chamados = $chamados
                    ->agroup()
                        ->like("c.nome", $data->q, null)
                        ->like("c.cidade", $data->q, "OR")
                    ->endAgroup();
                $params["q"] = $data->q;
            }
        }

        if ($this->guard == 'clientes') {

            $chamados = $chamados->where('ch.cliente_id', $this->user->cliente_id);
        }

        if ($data->has('order')) {

            if ($data->order=='data-asc')       $chamados = $chamados->orderBy('ch.data');
            elseif ($data->order=='data-desc')  $chamados = $chamados->orderBy('ch.data', 'desc');
            elseif ($data->order=='id-asc')     $chamados = $chamados->orderBy('ch.chamado_id');
            elseif ($data->order=='id-desc')    $chamados = $chamados->orderBy('ch.chamado_id', 'desc');

            $params["order"] = $data->order;

        } else {

            $chamados = $chamados->orderBy('ch.data');
        }

        $Here = new Here();

        if ($data->has('limit')) {

            $params["limit"] = $data->limit;

            $page = $data->has('page') ? $data->page : 1;

            $offset = ($page * $data->limit) - $data->limit;

            $total = count($chamados->get(false));

            $last_page = ceil($total / $data->limit);

            $next_page = $page+1 > $last_page ? null : $page+1;

            $prev_page = $page-1 == 0 ? null : $page-1;

            $dados = $chamados->limit($data->limit, $offset)->get();

            foreach ($dados as $d) {

                if ($d->status_id==5 && $d->cliente_coordenadas && $d->tecnico_localizacao) {

                    $trafficTime = $Here->summaryRoute($d->tecnico_localizacao, $d->cliente_coordenadas)->trafficTime;
                    $d->chamado_tempo = time_left($trafficTime);

                } else {

                    $d->chamado_tempo = null;
                }
            }

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

            $chamados = $chamados->get();

            foreach ($chamados as $d) {

                if ($d->status_id==5 && $d->cliente_coordenadas && $d->tecnico_localizacao) {

                    $trafficTime = $Here->summaryRoute($d->tecnico_localizacao, $d->cliente_coordenadas)->trafficTime;
                    $d->chamado_tempo = time_left($trafficTime);

                } else {

                    $d->chamado_tempo = null;
                }
            }

            $chamados = (array)$chamados;

            ksort($chamados);

            $response = $chamados;
        }

        return response_json($response);

    }

    public function insert()
    {

        $data = new Data();
        $data->json();

        $data->required(["tipo_id"]);

        $data->list("tipo_id", [1,2]);

        // Suprimento
        if ($data->tipo_id==1) {

            $data->required(["maquina_id", "suprimentos"]);
            $data->is_array(["suprimentos"]);
        }
        // elseif ($data->tipo_id==2) {

        //     $data->required(["mensagem"]);
        // }

        if ($this->guard=="clientes") {

            $data->add("cliente_id", $this->auth->user()->cliente_id);
            $data->add("tecnico_id", null);
            $data->add("abertura_local", "app_cliente");

        } elseif ($data->has("maquina_id")) {

            $maquina = Equipamento::findById($data->maquina_id);

            $data->add("cliente_id", $maquina->cliente_id);
        }

        if (isset($maquina) && $maquina->status_abertura==0)
            return response_json(["error"=>'Equipamento não pode ser usado'], 400);

        $data->required(["cliente_id"]);

        if ($this->guard=="tecnicos") {

            $data->add("tecnico_id", $this->auth->auth()->uid);
            $data->add("abertura_local", "app_tecnico");
        }

        $data->datetime(["data"], true);
        $data->add("abertura_usuario", $this->auth->auth()->uid);
        $data->add("status_id", 1);

        $data->remove(["suprimentos"]);

        $insert = Chamado::create($data->all());

        if ($insert->result==false) {


            return response_json(['error'=>'Erro ao inserir chamado'], 400);
        }

        // Inserir Suprimentos

        if ($data->has("suprimentos")) {

            $suprimentos = $data->suprimentos;

            foreach ($suprimentos as $s) {

                $qtde = $s["qtde"] ?? 1;

                ChamadoSuprimento::create(['chamado_id'=>$insert->id, 'suprimento_id'=>$s['suprimento_id'], 'quantidade'=>$qtde, 'valor'=>$s['suprimento_valor'] ]);
            }
        }

        // Inserir Histórico
        $hist["chamado_id"] = $insert->id;
        $hist["data"] = date("Y-m-d H:i:s");
        $hist["usuario_tipo"] = substr($this->guard, 0, -1);
        $hist["usuario_id"] = $this->auth->auth()->uid;
        $hist["local"] = "app_".substr($this->guard, 0, -1);
        $hist["tipo"] = "abrir";
        $hist["assunto"] = Historico::actions("abrir", "assunto");
        $hist["texto"] = Historico::actions("abrir", "mensagem")."<br>".$data->mensagem;
        $hist["publico"] = Historico::actions("abrir", "publico");
        $hist["localizacao"] = $data->has("localizacao") ? $data->localizacao : null;

        Historico::create($hist);

        // Enviar E-mail

        // Enviar Notificação
        Onesignal::sendNotification($this->auth->user()->notificacao_id, null, "Novo Chamado #".$insert->id);

        return response_json(['success'=>'Chamado inserido com sucesso', 'id'=>$insert->id]);
        // return response_json(['message'=>'Chamado inserido com sucesso', 'id'=>$insert->id]);

    }

    public function start()
    {
        $this->auth->checkGuard("tecnicos");

        $data = new Data();
        $data->json();

        $data->required(["chamado_id"]);
        $data->allow(["chamado_id"]);
        $data->is_int(["chamado_id"]);

        $id = $data->id("chamado_id");

        $chamado = Chamado::findById($id);

        $this->checkTecnico($chamado, "iniciar");

        // if ($chamado->status_id!=1)
            // return response_json(["error" => 'Este chamado não pode ser iniciado. O status deve ser 1: "Novo Chamado" '], 400);

        $this->checkStatus($chamado, [2,3,4,5]);

        $up["status_id"] = 5;
        $up["data_inicio"] = date('Y-m-d H:i:s');

        $update = Chamado::update($id, $up);

        if (!$update)
            return response_json(['error'=>'Erro ao iniciar chamado'], 400);
            // return response_json(['message'=>'Erro ao iniciar chamado'], 400);

        // Inserir Histórico
        $hist["chamado_id"] = $id;
        $hist["data"] = date("Y-m-d H:i:s");
        $hist["usuario_tipo"] = substr($this->guard, 0, -1);
        $hist["usuario_id"] = $this->auth->auth()->uid;
        $hist["local"] = "app_".substr($this->guard, 0, -1);
        $hist["tipo"] = "transito";
        $hist["assunto"] = Historico::actions("transito", "assunto");
        $hist["texto"] = Historico::actions("transito", "mensagem");
        $hist["publico"] = Historico::actions("transito", "publico");
        $hist["localizacao"] = $data->has("localizacao") ? $data->localizacao : null;

        Historico::create($hist);

        // Enviar E-mail

        // Enviar Notificação ??
        // $cliente = Cliente::findById($chamado->cliente_id);
        // Onesignal::sendNotification($this->auth->user()->notificacao_id, "Chamado #".$id.": Em Trânsito", "O técnico já está à caminho.");


        return response_json(["success" => "Atendimento Iniciado"]);
        // return response_json(["message" => "Atendimento Iniciado"]);
    }

    public function checkin()
    {
        $this->auth->checkGuard("tecnicos");

        $data = new Data();
        $data->json();

        $data->required(["chamado_id"]);
        $data->allow(["chamado_id", "localizacao"]);
        $data->is_int(["chamado_id"]);

        $id = $data->id("chamado_id");

        $chamado = Chamado::findById($id);

        $this->checkTecnico($chamado, "checar");

        $this->checkStatus($chamado, [2,3,4]);

        $configuracao = Configuracao::findById(1);

        // Checar distância
        if ($configuracao->chamados_distancia_checkin) {

            $cliente = Cliente::findById($chamado->cliente_id);

            if ($cliente->coordenadas) {

                $localizacao_tecnico = $data->has("localizacao") ? $data->localizacao : $this->auth->user()->localizacao;

                $distancia = distancia($localizacao_tecnico, $cliente->coordenadas, "m");

                if ($distancia>$configuracao->chamados_distancia_checkin)
                    return response_json(['error'=>'Erro ao tentar realizar o Checkin. Você não está próximo suficiente do cliente'], 400);
            }
        }

        $up["status_id"] = 2;
        $up["data_checkin"] = date('Y-m-d H:i:s');

        $update = Chamado::update($id, $up);

        if (!$update)
            return response_json(['error'=>'Erro ao tentar realizar o Checkin'], 400);

        // Inserir Histórico
        $hist["chamado_id"] = $id;
        $hist["data"] = date("Y-m-d H:i:s");
        $hist["usuario_tipo"] = substr($this->guard, 0, -1);
        $hist["usuario_id"] = $this->auth->auth()->uid;
        $hist["local"] = "app_".substr($this->guard, 0, -1);
        $hist["tipo"] = "checkin";
        $hist["assunto"] = Historico::actions("checkin", "assunto");
        $hist["texto"] = Historico::actions("checkin", "mensagem");
        $hist["publico"] = Historico::actions("checkin", "publico");
        $hist["localizacao"] = $data->has("localizacao") ? $data->localizacao : null;

        Historico::create($hist);

        // Enviar E-mail

        // Enviar Notificação ??
        // $cliente = Cliente::findById($chamado->cliente_id);
        // Onesignal::sendNotification($cliente->notificacao_id, "Chamado #".$id.": Checkin Realizado", "O técnico já está atendendo o chamado #".$id);

        return response_json(["success" => "Checkin Realizado com Sucesso"]);
    }

    /**
     * Trocar Equipamento
     */
    public function equipamento()
    {
        $this->auth->checkGuard("tecnicos");

        $data = new Data();
        $data->json();

        $data->required(["chamado_id"]);
        $data->allow(["chamado_id", "maquina_id"]);
        $data->is_int(["chamado_id", "maquina_id"]);
        $data->nullIfEmpty(["maquina_id"]);

        $id = $data->id("chamado_id");

        $chamado = Chamado::findById($id);

        $this->checkTecnico($chamado);

        $this->checkStatus($chamado, [3,4]);

        if ($data->has("maquina_id")) {

            $maquina = Equipamento::findById($data->maquina_id);

            if (!$maquina) {

                return response_json(["error"=>'Equipamento não encontrado'], 400);

            } elseif ($maquina->cliente_id != $chamado->cliente_id) {

                return response_json(["error"=>'Equipamento não pertence à este cliente'], 400);

            } elseif ($maquina->status_abertura==0) {

                return response_json(["error"=>'Equipamento não pode ser usado'], 400);
            }
        }

        if ($data->has("maquina_id")==false) {

            $act_historico = "excluir_equipamento";
        }
        elseif ($chamado->maquina_id==null) {

            $act_historico = "inserir_equipamento";
        }
        else {

            $act_historico = "alterar_equipamento";
        }


        // Atualizar Chamado
        $update = Chamado::update($id, ['maquina_id'=>$data->maquina_id]);

        if (!$update) {
            return response_json(['error'=>'Erro ao alterar equipamento'], 400);
            // return response_json(['message'=>'Erro ao alterar equipamento'], 400);
        }

        // Inserir Histórico
        $hist["chamado_id"] = $id;
        $hist["data"] = date("Y-m-d H:i:s");
        $hist["usuario_tipo"] = substr($this->guard, 0, -1);
        $hist["usuario_id"] = $this->auth->auth()->uid;
        $hist["local"] = "app_".substr($this->guard, 0, -1);
        $hist["tipo"] = $act_historico;
        $hist["assunto"] = Historico::actions($act_historico, "assunto");
        $hist["texto"] = Historico::actions($act_historico, "mensagem"). " #" .$data->maquina_id;
        $hist["publico"] = Historico::actions($act_historico, "publico");
        $hist["localizacao"] = $data->has("localizacao") ? $data->localizacao : null;

        Historico::create($hist);

        // Enviar E-mail

        // Enviar Notificação

        return response_json(['success' => 'Equipamento alterado com sucesso']);

    }

    /**
     * Colocar o Chamado em Pendência
     */
    public function pendency()
    {

        $data = new Data();
        $data->json();

        $data->required(["chamado_id"]);
        $data->is_int(["chamado_id"]);

        $id = $data->id("chamado_id");

        $chamado = Chamado::findById($id);

        // Checando APP do Técnico
        if ($this->guard=="tecnicos") {

            $this->checkTecnico($chamado, "atualizar");

        } elseif ($this->guard=="clientes") {

            $this->checkCliente($chamado);
        }

        /**
         * Chamado não pode ser colocado em pendência caso esteja com os status:
         * 3 = Finalizado
         * 4 = Cancelado
         * 6 = Pendente
         */
        $this->checkStatus($chamado, [3,4,6]);

        // Atualizar Chamado
        $update = Chamado::update($id, ["status_id"=>6]);

        // Atualizar Equipamento
        Equipamento::update($chamado->maquina_id, ["operante"=>$data->operante]);

        // Gravar se o chamado tiver peças
        $pecas = $data->has("pecas") ? $data->pecas : false;
        $servicos = $data->has("servicos") ? $data->servicos : false;
        $suprimentos = $data->has("suprimentos") ? $data->suprimentos : false;

        $texto_historico = $data->texto;

        if ($pecas) {
            $this->insertPecas($id, $pecas, true);

            $texto_historico .= "\n\nLista de peças utilizadas:";

            foreach ($pecas as $p) {

                $peca = Peca::find($p["peca_id"]);
                $texto_historico .= "\n" . $p["qtde"] . " " . $peca->nome;
            }
        }

        if ($servicos) {
            $this->insertServicos($id, $servicos, true);

            $texto_historico .= "\n\nLista de Serviços realizados:";

            foreach ($servicos as $s) {
                $servicos_ids[] = $s["servico_id"];
            }

            $servicos_nomes = Servico::whereIn($servicos_ids)->pluck("nome");

            $texto_historico .= "\n" . implode("\n", $servicos_nomes);
        }

        if ($suprimentos) {
            $this->insertSuprimentos($id, $suprimentos, true);

            $texto_historico .= "\n\nLista de Suprimentos utilizados:";

            foreach ($suprimentos as $s) {

                $suprimento = Suprimento::find($s["suprimento_id"]);
                $texto_historico .= "\n" . $s["qtde"] . " " . $suprimento->nome;
            }
        }

        // Gravar Contadores
        if ($data->has("contador_mono")||$data->has("contador_color")) {

            Contagem::create([
                "equipamento" => $chamado->maquina_id,
                "chamado_id" => $id,
                "mono" => $data->contador_mono??null,
                "color" => $data->contador_color??null,
            ]);

            $texto_historico .= "\n";
            if ($data->has("contador_mono")) {
                $texto_historico .= "\nContador Mono: ".$data->contador_mono;
            }
            if ($data->has("contador_color")) {
                $texto_historico .= "\nContador Color: ".$data->contador_color;
            }
        }

        if (!$update)
            return response_json(['error'=>'Erro ao tentar atualizar o chamado'], 400);

        // Inserir Histórico
        $hist["chamado_id"] = $id;
        $hist["data"] = date("Y-m-d H:i:s");
        $hist["usuario_tipo"] = substr($this->guard, 0, -1); // tecnico ou cliente
        $hist["usuario_id"] = $this->auth->auth()->uid;
        $hist["local"] = "app_".substr($this->guard, 0, -1); // app_tecnico ou app_cliente
        $hist["tipo"] = "pendente";
        $hist["assunto"] = Historico::actions("pendente", "assunto");
        $hist["texto"] = Historico::actions("pendente", "mensagem").': '.$texto_historico;
        $hist["publico"] = Historico::actions("pendente", "publico");
        $hist["localizacao"] = $data->has("localizacao") ? $data->localizacao : null;
        $hist["tecnico"] = $this->guard=="tecnicos" ? 1 : 0;

        Historico::create($hist);

        // Enviar E-mail

        // Enviar Notificação
        // $cliente = Cliente::findById($chamado->cliente_id);
        // Onesignal::sendNotification($cliente->notificacao_id, "Chamado #".$id.": Cancelado", "O chamado #".$id." foi cancelado");

        return response_json(["success" => "Chamado atualizado com sucesso"]);

    }

    public function cancel()
    {

        // $this->auth->checkGuard("tecnicos");

        $data = new Data();
        $data->json();

        $data->required(["chamado_id", "mensagem_cancelamento"]);
        $data->is_int(["chamado_id"]);

        $id = $data->id("chamado_id");

        $chamado = Chamado::findById($id);

        if ($this->guard=="tecnicos") {

            $this->checkTecnico($chamado, "cancelar");

        }
        elseif ($this->guard=="clientes") {

            $this->checkCliente($chamado);
        }

        $this->checkStatus($chamado, [3,4]);

        // Atualizar
        $up["status_id"] = 4;
        $up["mensagem_cancelamento"] = $data->mensagem_cancelamento;
        $up["data_cancelamento"] = date("Y-m-d H:i:s");

        $update = Chamado::update($id, $up);

        if (!$update)
            return response_json(['error'=>'Erro ao tentar cancelar o chamado'], 400);
            // return response_json(['message'=>'Erro ao tentar cancelar o chamado'], 400);

        // Inserir Histórico
        $hist["chamado_id"] = $id;
        $hist["data"] = date("Y-m-d H:i:s");
        $hist["usuario_tipo"] = substr($this->guard, 0, -1);
        $hist["usuario_id"] = $this->auth->auth()->uid;
        $hist["local"] = "app_".substr($this->guard, 0, -1);
        $hist["tipo"] = "cancelar";
        $hist["assunto"] = Historico::actions("cancelar", "assunto");
        $hist["texto"] = Historico::actions("cancelar", "mensagem").' '.$data->mensagem_cancelamento;
        $hist["publico"] = Historico::actions("cancelar", "publico");
        $hist["localizacao"] = $data->has("localizacao") ? $data->localizacao : null;

        Historico::create($hist);

        // Enviar E-mail

        // Enviar Notificação
        // $cliente = Cliente::findById($chamado->cliente_id);
        // Onesignal::sendNotification($cliente->notificacao_id, "Chamado #".$id.": Cancelado", "O chamado #".$id." foi cancelado");

        return response_json(["success" => "Chamado cancelado com sucesso"]);
    }

    public function finish()
    {

        $this->auth->checkGuard("tecnicos");

        $data = new Data();
        $data->json();

        $data->required(["chamado_id"]);
        $data->is_int(["chamado_id", "contador_mono", "contador_color"]);
        $data->datetime(["data_solucao"]);

        $id = $data->id("chamado_id");

        $chamado = Chamado::findById($id);

        $this->checkTecnico($chamado, "finalizar");

        $this->checkStatus($chamado, [3,4]);

        $up["data_solucao"] = $data->has("data_solucao") ? $data->data_solucao : date('Y-m-d H:i:s');
        $up['status_id']    = 3;

        if ($data->has("assinatura")) {

            // $output_file = 'ass_'.$id.'.png';
            // $file = fopen($output_file, "wb");
            // $img = explode(',', $data->assinatura);
            // fwrite($file, base64_decode($img[1]));
            // fclose($file);

            // $path = '/storage/media/'.TOKEN_CLIENT.'/assinaturas/';

            // // Upload
            // try {

            //     $ftp = new FTP();
            //     $ftp->setPassive(true);
            //     $ftp->upload( fopen($output_file, 'r'), $path.$output_file);

            //     $File = new File();
            //     $File->remove($output_file);

            // } catch (\Exception $e) {

            //     $this->log->exception($e);
            //     return response_json(["error"=>$e->getMessage()], 503);
            // }

            $up["assinatura_nome"] = $data->assinatura_nome;

            // Gravar assinatura
            // $up["assinatura"] = $data->assinatura;
            Assinatura::create([
                "id_chamado" => $id,
                "assinatura" => $data->assinatura
            ]);
        }

        // Contadores
        if ($data->has("contador_mono")) {
            $up["contador_mono"] = $data->contador_mono;
        }
        if ($data->has("contador_color")) {
            $up["contador_color"] = $data->contador_color;
        }

        // Calcular SLA
        $cliente = Cliente::findById($chamado->cliente_id);
        $util = $cliente->hora_id==1 ? true : false;

        $up["sla"] = SLA::calculate($chamado->data, $up["data_solucao"], $util);

        // Enviar Email ao sistema da Coopercitrus
        if ($chamado->topdesk) {

            // Inserir tarefa na tabela de task_topdesk. Roda no cronjob
            DB::table("task_topdesk")->insert(["codigo"=>$chamado->topdesk, "acao"=>"encerrar", "mensagem"=>$chamado->mensagem_solucao]);
        }

        // Atualizar
        try {

            $update = Chamado::update($id, $up);

        } catch (Exception $e) {

            $this->log->exception($e);
        }

        if ($chamado->tipo_id==1) {

            // Verificar despacho e atualizar
            DB::table("despacho")->where("chamado_id", $chamado->chamado_id)->update(["status"=>"entregue", "entrega"=>$data->data_solucao]);
        }

        if (!$update) {

            $this->log->erro("Erro ao finalizar chamado", [
                "request"=> $_REQUEST,
                "body"=> $data->all(),
            ]);

            return response_json(["error"=>'Erro ao finalizar chamado'], 400);
        }

        /**
         * Baixar Estoques
         */
        // Baixar Peças
        ChamadoPeca::where("chamado_id", $id)->update(["baixado"=>1]);

        // Baixar Suprimentos
        ChamadoSuprimento::where("chamado_id", $id)->update(["baixado"=>1]);

        // Baixar Serviços
        ChamadoServico::where("chamado_id", $id)->update(["baixado"=>1]);


        // Adicionar Avaliação
        $avaliacao["cliente_id"] = $chamado->cliente_id;
        $avaliacao["chamado_id"] = $chamado->chamado_id;
        $avaliacao["tecnico_id"] = $chamado->tecnico_id;
        $avaliacao["status"]     = 0;
        Avaliacao::create($avaliacao);


        // Adicionar Histórico
        $historico["chamado_id"] = $id;
        $historico["data"] = date("Y-m-d H:i:s");
        $historico["usuario_tipo"] = substr($this->guard, 0, -1);
        $historico["usuario_id"] = $this->auth->auth()->uid;
        $historico["local"] = "app_".substr($this->guard, 0, -1);
        $historico["tipo"] = "finalizar";
        $historico["assunto"] = Historico::actions("finalizar", "assunto");
        $historico["texto"] = Historico::actions("finalizar", "mensagem");
        $historico["publico"] = Historico::actions("finalizar", "publico");
        $historico["localizacao"] = $data->has("localizacao") ? $data->localizacao : null;
        Historico::create($historico);


        // Enviar email Cliente?

        // Enviar Notificação
        // Onesignal::sendNotification($cliente->notificacao_id, null, "O chamado #".$id." foi finalizado");
        // Onesignal::sendNotification($cliente->notificacao_id, null, "Você possui novas avaliações");

        $this->log->info("Chamado finalizado com sucesso", [
            "request"=> $_REQUEST,
            "body"=> $data->all(),
        ]);

        return response_json(["success" => 'Chamado finalizado com sucesso']);
    }


    public function update($request)
    {

        $this->auth->checkGuard("tecnicos");

        $data = new Data();
        $data->json(false);

        $id = $data->id("chamado_id");

        $permitidos = [
            "chamado_id",
            "maquina_id",
            "contagem",
            "problemas",
            "mensagem_solucao",
            "status_id",
            "tecnico_id",
            "local_solucao",
            "localizacao",
            "data_previsao",
            "pecas",
            "servicos",
            "suprimentos",
            "contador_mono",
            "contador_color",
        ];

        if ($data->has("motivo")) {
            $motivo = $data->motivo;
            $data->del("motivo");
        }

        $data->allow($permitidos);

        $data->is_int(["chamado_id", "status_id", "tecnico_id"]);
        $data->datetime(["data_previsao"]);

        $pecas = $data->has("pecas") ? $data->pecas : false;
        $servicos = $data->has("servicos") ? $data->servicos : false;
        $suprimentos = $data->has("suprimentos") ? $data->suprimentos : false;

        $data->remove(["chamado_id", "pecas", "servicos", "suprimentos"]);

        $chamado = Chamado::findById($id);

        $this->checkTecnico($chamado, "finalizar");

        $this->checkStatus($chamado, [3,4]);

        // Se o Status for Agendar
        if ($data->has('status_id') && $data->status_id==7) {

            $hist_texto = Historico::actions("agendar", "mensagem") . " " . datebr($data->data_previsao).'<br>'.($motivo??"");

            // Agendar
            // Inserir Histórico
            $hist["chamado_id"] = $id;
            $hist["data"] = date("Y-m-d H:i:s");
            $hist["usuario_tipo"] = substr($this->guard, 0, -1);
            $hist["usuario_id"] = $this->auth->auth()->uid;
            $hist["local"] = "app_".substr($this->guard, 0, -1);
            $hist["tipo"] = "agendar";
            $hist["assunto"] = Historico::actions("agendar", "assunto");
            $hist["texto"] = $hist_texto;
            $hist["publico"] = Historico::actions("agendar", "publico");
            $hist["localizacao"] = $data->has("localizacao") ? $data->localizacao : null;

            Historico::create($hist);

        } else {

            $data->data_previsao = null;

            if ($pecas) {
                $this->insertPecas($id, $pecas);
            }

            if ($servicos) {
                $this->insertServicos($id, $servicos);
            }

            if ($suprimentos) {
                $this->insertSuprimentos($id, $suprimentos);
            }

            // Atualizar Equipamento
            if ($data->has("maquina_id")) {

                if ($data->maquina_id && ($data->maquina_id != $chamado->maquina_id)) {

                    if (!$data->maquina_id) {

                        $act_historico = "excluir_equipamento";

                        $update_maquina = Chamado::update($id, ['maquina_id'=>null]);

                    } else {

                        $maquina = Equipamento::findById($data->maquina_id);

                        if (!$maquina) {

                            return response_json(["error"=>'Equipamento não encontrado'], 400);

                        } elseif ($maquina->cliente_id != $chamado->cliente_id) {

                            return response_json(["error"=>'Equipamento não pertence à este cliente'], 400);

                        } elseif ($maquina->status_abertura==0) {

                            return response_json(["error"=>'Equipamento não pode ser usado'], 400);
                        }

                        $act_historico = "alterar_equipamento";

                        $update_maquina = Chamado::update($id, ['maquina_id'=>$data->maquina_id]);
                    }

                    if (!$update_maquina) {

                        return response_json(['error'=>'Erro ao alterar equipamento'], 400);
                    }

                    // Inserir Histórico
                    $hist["chamado_id"] = $id;
                    $hist["data"] = date("Y-m-d H:i:s");
                    $hist["usuario_tipo"] = substr($this->guard, 0, -1);
                    $hist["usuario_id"] = $this->auth->auth()->uid;
                    $hist["local"] = "app_".substr($this->guard, 0, -1);
                    $hist["tipo"] = $act_historico;
                    $hist["assunto"] = Historico::actions($act_historico, "assunto");
                    $hist["texto"] = Historico::actions($act_historico, "mensagem").($data->maquina_id?" #".$data->maquina_id:"");
                    $hist["publico"] = Historico::actions($act_historico, "publico");
                    $hist["localizacao"] = $data->has("localizacao") ? $data->localizacao : null;

                    Historico::create($hist);

                    $data->del("maquina_id");

                }

            }

        }

        // Atualizar
        $update = Chamado::update($id, $data->all());

        if (!$update) {

            $this->log->app("Request", [
                // "request"=> $_REQUEST,
                "erro ao atualizar"
            ]);

            return response_json(["error" => "Erro ao atualizar chamado"], 400);
        }


        return response_json(["success" => "Chamado salvo com sucesso"]);

    }


    public function delete()
    {

        $data = new Data();
        $data->json();
        $data->required("chamado_id");
        $data->allow("chamado_id");

        $id = $data->id("chamado_id");

        $chamado = Chamado::findById($id);

        if (!$chamado)
            return response_json(["error"=>"Chamado não encontrado"], 400);
            // return response_json(["message"=>"Chamado não encontrado"], 400);

        if ($this->guard=="tecnicos") {

            $this->checkTecnico($chamado, "excluir");

        }
        elseif ($this->guard=="clientes") {

            $this->checkCliente($chamado);
        }

        $delete = Chamado::delete($id,"chamado_id");

        if (!$delete)
            return response_json(["error"=>"Erro ao excluir chamado"], 400);
            // return response_json(["message"=>"Erro ao excluir chamado"], 400);

        // Deletar Arquivos
        $ftp = new FTP();

        if ($chamado->anexo)
            $ftp->delete('/storage/media/'.TOKEN_CLIENT.'/chamados/' . $chamado->anexo);

        if ($chamado->assinatura)
            $ftp->delete('/storage/media/'.TOKEN_CLIENT.'/assinaturas/ass_' . $id . '.png');

        // Deletar pecas
        ChamadoPeca::where("chamado_id", $id)->delete();
        // Deletar servicos
        ChamadoServico::where("chamado_id", $id)->delete();
        // Deletar suprimentos
        ChamadoSuprimento::where("chamado_id", $id)->delete();

        return response_json(["success" => "Chamado excluído com sucesso"]);
        // return response_json(["message" => "Chamado excluído com sucesso"]);
    }

    public function upload($request)
    {

        $data = new Data($request);
        $data->required(["chamado_id"]);
        $data->is_int(["chamado_id"]);
        $id = $data->id("chamado_id");

        $chamado = Chamado::findById($id);

        if (!$chamado)
            return response_json(["error"=>"Chamado não encontrado"], 400);

        if ($this->guard=="tecnicos") {

            $this->checkTecnico($chamado, "alterar");

        }
        elseif ($this->guard=="clientes") {

            $this->checkCliente($chamado);
        }

        // if ($chamado->status_id!=1)
            // return response_json(['error' => 'Este chamado não pode mais receber anexos'], 400);

        if (isset($_FILES["file"])) {  //anexo

            $File = new File();
            $ftp = new FTP();

            $path = '/storage/media/'.TOKEN_CLIENT.'/chamados/';

            $liberados = ["jpg", "jpeg", "png", "txt", "bmp", "doc", "docx", "pdf"];

            // Reagrupar array de arquivos
            if (is_array($_FILES["file"]["name"])) {

                $anexos = reArrayFiles($_FILES["file"]);

            } else {

                $anexos[0] = $_FILES["file"];
            }

            foreach ($anexos as $file) {

                // printa($file);

                if ($File->valid($file["tmp_name"])) {

                    $ext = $File->extension($file["name"]);

                    if (in_array($ext, $liberados)) {

                        if ($chamado->anexo) {

                            $ftp->delete( $path.$chamado->anexo );
                        }

                        $fname = 'anexo_'.$chamado->chamado_id.'_'.date('YmdHis').'.'.$ext;

                        $ftp->setMode(FTP_BINARY);
                        $ftp->setPassive(true);
                        $upload = $ftp->upload( $file['tmp_name'] , $path.$fname, false);

                        if (!$upload)
                            return response_json(['error' => 'Houve um erro ao tentar subir o arquivo'], 400);

                        // Se for cliente guardar a imagem no campo "anexo" da tabela chamados
                        if ($this->guard=="clientes") {

                            $update = Chamado::update($id, ["anexo"=>$fname]);

                            if (!$update)
                                return response_json(['error'=>'Erro ao gravar anexo ao chamado'], 400);

                        }
                        elseif ($this->guard=="tecnicos") {

                            // Adicionar arquivo no banco
                            $arquivo["chamados_arquivos_chamado"] = $id;
                            $arquivo["chamados_arquivos_data"] = date("Y-m-d H:i:s");
                            $arquivo["chamados_arquivos_arquivo"] = $fname;
                            $arquivo["chamados_arquivos_tipo"] = substr($this->guard, 0, -1);
                            $arquivo["chamados_arquivos_usuario"] = $this->auth->auth()->uid;

                            // Upload no banco de dados
                            $insert = ChamadoArquivo::insert($arquivo);

                            if (!$insert->result)
                                return response_json(['error'=>'Erro ao gravar anexo ao chamado'], 400);
                        }

                    } else {

                        return response_json(['error'=>'Extensão de arquivo não permitida'], 400);
                    }

                } else {

                    return response_json(['error'=>'Arquivo '.$file["name"].' inválido'], 400);
                }
            }

            $ftp->close();
        }


        return response_json(['success' => 'Arquivo adicionado com sucesso']);

    }

    /**
     * Função para ver se o técnico é o responsável pelo chamado
     * @param string act verbo da legenda para mostrar na mensagem de retorno
     */
    private function checkTecnico($chamado, $act=null)
    {
        $act = $act ?? "visualizar";

        if (isset($chamado->tecnico_id) != $this->auth->user()->tecnico_id)
            return response_json(["error"=>'Técnico não autorizado à '.$act.' este chamado'], 400);
    }

    /**
     * Função para ver se o chamado pertence ao cliente autenticado
     * @param string act varbo da legenda para mostrar na mensagem de retorno
     */
    private function checkCliente($chamado)
    {
        if ($chamado->cliente_id != $this->auth->user()->cliente_id)
            return response_json(["error"=>'O Chamado não pertence à este cliente'], 400);
    }

    /**
     * Checar o status do chamado para validar se ação pode ser realizada
     */
    private function checkStatus($chamado, $status)
    {

        $messages = [
            1 => "Este chamado está marcado como novo",
            2 => "Este chamado está em atendimento",
            3 => "Este chamado já foi finalizado",
            4 => "Este chamado está cancelado",
            5 => "Este chamado está Em trânsito",
            6 => "Este chamado está com alguma pendência",
            7 => "Esta chamado está agendado",
        ];

        $status = (array)$status;
        foreach ($status as $s) {

            if ($chamado->status_id==$s)
                return response_json(["error" => 'Não foi possível esta ação. '.$messages[$s]], 400);

        }
    }

    /**
     * Incluir peças no chamado
     */
    private function insertPecas($id_chamado, $pecas, $baixar=false)
    {
        $items = [];

        if (count($pecas)>0) {

            foreach ($pecas as $p) {

                if (is_array($p)) {

                    $f["chamado_id"] = $id_chamado;
                    $f["peca_id"] = $p["peca_id"];
                    $f["quantidade"] = $p["qtde"] ?? 1;
                    $f["valor"] = $p["peca_valor"]??null;
                    $f["baixado"] = $baixar?1:0;
                    $f["tecnico_id"] = $this->user->tecnico_id;

                    // Se já existir atualizar
                    if (isset($p["id"])) {

                        ChamadoPeca::where("id", $p["id"])->update($f);
                        $items[] = $p["id"];

                    } else {

                        // Senão inserir no banco
                        $insert = ChamadoPeca::create($f);
                        $items[] = $insert->id;
                    }
                }
            }
        }

        // Deletar peças que não estão no objeto atual
        ChamadoPeca::where("chamado_id", $id_chamado)
            ->where("baixado", 0)
            ->notIn($items)
            ->delete();

    }

    /**
     * Incluir serviços no chamado
     */
    private function insertServicos($id_chamado, $servicos, $baixar=false)
    {

        $items = [];

        if (count($servicos)>0) {

            foreach ($servicos as $s) {

                if (is_array($s)) {

                    $f["chamado_id"] = $id_chamado;
                    $f["servico_id"] = $s["servico_id"];
                    $f["quantidade"] = $s["qtde"] ?? 1;
                    $f["valor"] = $s["servico_valor"]??null;
                    $f["baixado"] = $baixar?1:0;
                    $f["tecnico_id"] = $this->user->tecnico_id;

                    // Se já existir atualizar
                    if (isset($s["id"])) {

                        ChamadoServico::where("id", $s["id"])->update($f);
                        $items[] = $s["id"];

                    } else {

                        // Senão inserir no banco
                        $insert = ChamadoServico::create($f);
                        $items[] = $insert->id;
                    }
                }
            }
        }

        // Deletar serviços que não estão no objeto atual
        ChamadoServico::where("chamado_id", $id_chamado)
            ->where("baixado", 0)
            ->notIn($items)
            ->delete();
    }

    /**
     * Incluir suprimentos no chamado
     */
    private function insertSuprimentos($id_chamado, $suprimentos, $baixar=false)
    {
        $items = [];

        if (count($suprimentos)>0) {

            foreach ($suprimentos as $s) {

                if (is_array($s)) {

                    $f["chamado_id"] = $id_chamado;
                    $f["suprimento_id"] = $s["suprimento_id"];
                    $f["quantidade"] = $s["qtde"] ?? 1;
                    $f["valor"] = $s["suprimento_valor"]??null;
                    $f["baixado"] = $baixar?1:0;
                    $f["tecnico_id"] = $this->user->tecnico_id;

                    // Se já existir atualizar
                    if (isset($s["id"])) {

                        ChamadoSuprimento::where("id", $s["id"])->update($f);
                        $items[] = $s["id"];

                    } else {

                        // Senão inserir no banco
                        $insert = ChamadoSuprimento::create($f);
                        $items[] = $insert->id;
                    }
                }
            }
        }

        // Deletar suprimentos que não estão no objeto atual
        ChamadoSuprimento::where("chamado_id", $id_chamado)
            ->where("baixado", 0)
            ->notIn($items)
            ->delete();
    }


}

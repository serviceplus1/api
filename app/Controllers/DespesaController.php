<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Data;
use App\Core\File;
use App\Core\FTP;
use App\Models\Despesa;
use App\Models\DespesaCategoria;
use App\Models\Forma;
use Exception;

class DespesaController extends Controller
{

    public function __construct($router)
    {
        parent::__construct($router);

        $this->auth = new Auth("tecnicos");

        if (parent::loggedApi()===false)
            unauthorized();

        $this->auth->checkGuard("tecnicos");

    }

    public function categorias()
    {
        $categorias = DespesaCategoria::order("descricao")->get();
        return response_json($categorias);
    }

    public function insertCategoria()
    {

        $data = new Data();
        $data->json();
        $data->required(["descricao"]);
        $data->allow(["descricao"]);

        $insert = DespesaCategoria::create($data->all());

        if ($insert->result==false)
            return response_json(["error" => "Erro ao inserir categoria"], 400);
            // return response_json(["message" => "Erro ao inserir categoria"], 400);

        return response_json(['success'=>'Categoria inserida com sucesso', 'id'=>$insert->id]);
        // return response_json(['message'=>'Categoria inserida com sucesso', 'id'=>$insert->id]);
    }

    public function show($request)
    {

        $data = new Data($request);
        $data->required(["id_despesa"]);
        $data->is_int(["id_despesa"]);

        $fields = [

            'd.id AS id',
            'd.data AS data',
            'd.descricao AS descricao',
            'd.local AS local',
            'd.valor AS valor',
            'd.imagem AS imagem',
            'd.observacoes AS observacoes',
            'd.reembolso AS reembolso',
            'd.uuid AS uuid',
            'd.tecnico_id AS tecnico_id',
            't.tecnico_nome AS tecnico_nome',
            'd.categoria_id AS categoria_id',
            'c.descricao AS categoria',
            'd.forma_id AS forma_id',
            'f.descricao AS forma',
            'd.aprovacao AS aprovacao',
        ];

        $despesa = Despesa::join('tecnicos AS t', 'd.tecnico_id', 't.tecnico_id')
                    ->leftJoin('desp_categorias AS c', 'd.categoria_id', 'c.id')
                    ->leftJoin('formas_pagamento AS f', 'd.forma_id', 'f.id')
                    ->select($fields)
                    ->where("d.id",$data->id_despesa)
                    ->first();

        if (!$despesa) {
            return response_json(['error'=>'Despesa não encontrada'], 400);
            // return response_json(['message'=>'Despesa não encontrada'], 400);

        }
        elseif ($despesa->tecnico_id != $this->auth->user()->tecnico_id) {

            return response_json(['error'=>'Despesa não pertence ao técnico'], 400);
            // return response_json(['message'=>'Despesa não pertence ao técnico'], 400);
        }

        $despesa->id = (int)$despesa->id;

        if ($despesa->imagem) {
            $despesa->imagem = 'https://app.serviceplus.com.br/storage/media/'.TOKEN_CLIENT.'/despesas/'.$despesa->imagem;
        }

        return response_json($despesa);
    }


    public function list()
    {

        $data = new Data($_GET);
        $data->is_date(["inicio", "fin"]);
        $data->is_int(["categoria", "forma", "limit"]);
        $data->date(["inicio", "fim"]);
        $data->min(["limit"], 1);

        /* teste */
        $data->add("inicio", date("Y-m-01"));
        $data->add("fim", date("Y-m-t"));

        // printa($data->all());

        $fields = [
            'd.id AS id',
            'd.data AS data',
            'd.descricao AS descricao',
            'd.valor AS valor',
            'd.local AS local',
            'd.tecnico_id AS tecnico_id',
            't.tecnico_nome AS tecnico',
            'd.forma_id AS forma_id',
            'f.descricao AS forma',
            'd.categoria_id AS categoria_id',
            'c.descricao AS categoria'
        ];

        $despesas = Despesa::join('tecnicos as t', 'd.tecnico_id', 't.tecnico_id')
            ->leftJoin('formas_pagamento as f', 'd.forma_id', 'f.id')
            ->leftJoin('desp_categorias as c', 'd.categoria_id', 'c.id')
            ->where('d.tecnico_id', $this->auth->auth()->uid);

        if ($data->has('categoria')) {

            $despesas = $despesas->where('d.categoria_id', $data->categoria);
        }

        if ($data->has('forma')) {

            $despesas = $despesas->where('d.forma_id', $data->forma);
        }

        if ($data->has('inicio') || $data->has('fim')) {

            if ($data->has('inicio') && $data->has('inicio')) {

                $despesas = $despesas->between('DATE(d.data)', $data->inicio, $data->fim);

            }
            else if ($data->has('inicio')) {

                $despesas = $despesas->where('DATE(d.data)', $data->inicio, '>=');

            }
            else if ($data->has('fim')) {

                $despesas = $despesas->where('DATE(d.data)', $data->fim, '<=');

            }
        }

        $despesas = $despesas->select($fields)->orderBy('d.data', 'desc');

        if ($data->has('limit')) {

            $params["limit"] = $data->limit;

            $page = $data->has('page') ? $data->page : 1;

            $offset = ($page * $data->limit) - $data->limit;

            $total = count($despesas->get(false));

            $last_page = ceil($total / $data->limit);

            $next_page = $page+1 > $last_page ? null : $page+1;

            $prev_page = $page-1 == 0 ? null : $page-1;

            $dados = $despesas->limit($data->limit, $offset)->get();

            $response["current_page"] = $page;
            $response["data"] = $dados;
            $response["first_page_url"] = $this->router->route("api.despesa.list")."?".http_build_query($params)."&page=1";
            $response["from"] = $offset+1;
            $response["last_page"] = $last_page;
            $response["last_page_url"] = $this->router->route("api.despesa.list")."?".http_build_query($params)."&page=".$last_page;
            $response["next_page_url"] = $next_page==null ? null : $this->router->route("api.despesa.list")."?".http_build_query($params)."&page=".$next_page;
            $response["path"] = $this->router->route("api.despesa.list");
            $response["per_page"] = $data->limit;
            $response["prev_page_url"] = $prev_page==null ? null : $this->router->route("api.despesa.list")."?".http_build_query($params)."&page=".$prev_page;
            $response["to"] = $offset+$data->limit;
            $response["total"] = $total;

        } else {

            $response = $despesas->get();
        }

        return response_json($response, 200, false);

    }

    public function insert()
    {

        $data = new Data();
        $data->json();
        $data->add("tecnico_id", $this->auth->auth()->uid);

        $data->datetime(["data"], true);

        $data->required(["categoria_id", "descricao", "valor", "forma_id"]);
        $data->is_int(["categoria_id", "forma_id"]);
        $data->is_numeric(["valor"]);

        $data->zeroIfEmpty(["reembolso"]);

        if ($data->categoria_id==0)
            $data->required(["categoria"]);

        if ($data->forma_id==0)
            $data->required(["forma"]);

        // $this->log->app("Insert Despesa", [
        //     "json_body"=> $data->all(),
        // ]);

        // Inserir nova categoria
        if ($data->categoria_id==0) {

            $ins_categoria = DespesaCategoria::create(["descricao"=>$data->categoria]);
            $data->add("categoria_id", $ins_categoria->id);
        }
        $data->remove("categoria");

        // Inserir nova forma de pagamento
        if ($data->forma_id==0) {

            $ins_forma = Forma::create(['descricao'=>$data->forma]);
            $data->add("forma_id", $ins_forma->id);
        }
        $data->remove("forma");

        if ($data->has('imagem')) {

            $output_file = 'desp_'.date('YmdHis').'.jpg';
            $file = fopen($output_file, "wb");
            $img = explode(',', $data->imagem);
            fwrite($file, base64_decode($img[1]));
            fclose($file);

            $path = '/storage/media/'.TOKEN_CLIENT.'/despesas/';

            // Upload
            try {

                $ftp = new FTP();
                $ftp->setPassive(true);
                $ftp->upload( fopen($output_file, 'r') , $path.$output_file);

                $File = new File();
                $File->remove($output_file);

            } catch (\Exception $e) {

                return response_json(['error'=>$e->getMessage()], 503);
                // return response_json(['message'=>$e->getMessage()], 503);
            }

            $data->add("imagem", $output_file);
        }

        try {

            $insert = Despesa::firstOrCreate($data->all());

        } catch (Exception $e) {

            $this->log->exception($e);
        }

        if ($insert->result) {

            $this->log->info("Despesa Inserida com Sucesso", [
                "request"=> $_REQUEST,
                "body"=> $data->all(),
            ]);

            return response_json(['success'=>'Despesa inserida com sucesso', 'id'=>$insert->id]);

        }
        else if (isset($insert->result)&&$insert->result==false) {

            $this->log->erro("Erro ao inserir despesa", [
                "request"=> $_REQUEST,
                "body"=> $data->all(),
            ]);

            return response_json(["error" => "Erro ao inserir despesa"], 400);
        }

    }


    public function update()
    {

        $data = new Data();
        $data->json();

        // Log para ver os dados que estão chegando
        // $this->log->app("Insert Despesa", [
        //     "json_body"=> $data->all(),
        // ]);

        $data->required(["id"]);

        $id = $data->id();
        $data->remove("id");

        $data->datetime(["data"], true);

        $despesa = Despesa::find($id);

        if (!$despesa)
            return response_json(["message" => "Despesa não encontrada"], 400);

        if ($despesa->tecnico_id != $this->auth->auth()->uid)
            return response_json(['message'=>'Despesa não pertence ao técnico'], 400);

        // $data->add("tecnico_id", $this->auth->auth()->uid);

        $data->is_int(["categoria_id", "forma_id", "reembolso"]);
        $data->is_numeric(["valor"]);

        if ($data->has("categoria_id") && $data->categoria_id==0)
            $data->required(["categoria"]);

        if ($data->has("forma_id") && $data->forma_id==0)
            $data->required(["forma"]);

        // Inserir nova categoria
        if ($data->categoria_id==0) {

            $ins_categoria = DespesaCategoria::create(["descricao"=>$data->categoria]);
            $data->add("categoria_id", $ins_categoria->id);
        }
        $data->remove("categoria");

        // Inserir nova forma de pagamento
        if ($data->forma_id==0) {

            $ins_forma = Forma::create(['descricao'=>$data->forma]);
            $data->add("forma_id", $ins_forma->id);
        }
        $data->remove("forma");

        if ($data->has('imagem')) {

            $path = '/storage/media/'.TOKEN_CLIENT.'/despesas/';

            $ftp = new FTP();

            // Excluir Imagem Atual
            $ftp->delete($path . $despesa->imagem);

            // Upload
            try {

                $output_file = 'desp_'.date('YmdHis').'.jpg';
                $file = fopen($output_file, "wb");
                $img = explode(',', $data->imagem);
                fwrite($file, base64_decode($img[1]));
                fclose($file);

                $ftp->setPassive(true);
                $ftp->upload( fopen($output_file, 'r') , $path.$output_file);

                $File = new File();
                $File->remove($output_file);

            } catch (\Exception $e) {

                return response_json(['message'=>$e->getMessage()], 503);
            }

            $data->add("imagem", $output_file);
        }

        $update = Despesa::where("id", $id)->update($data->all());

        if ($update==false)
            return response_json(["message" => "Erro ao atualizar despesa"], 400);

        return response_json(['message'=>'Despesa atualizada com sucesso']);

    }


    public function delete()
    {

        $data = new Data();
        $data->json();

        $data->required("id");

        $id = $data->id();

        $despesa = Despesa::find($id);

        if (!$despesa) {

            return response_json(['message'=>'Despesa não encontrada'], 400);
        }
        elseif ($despesa->tecnico_id != $this->auth->user()->tecnico_id) {

            return response_json(['message'=>'Despesa não pertence ao técnico'], 400);
        }

        $delete = Despesa::delete($id);

        if (!$delete)
            return response_json(['message'=>'Erro ao excluir despesa'], 400);


        return response_json(['message' => 'Despesa excluída com sucesso'], 200);
    }

}

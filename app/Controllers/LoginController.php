<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Data;
use App\Core\Jwt;
use App\Core\Message;
use App\Models\Cliente;

class LoginController extends Controller
{

    public function __construct($router)
    {

        parent::__construct($router);

    }

    public function cliente()
    {

        $data = new Data();
        $data->json();
        $data->required(["login", "senha"]);

        // if ($data->versao != APP_VERSION)
            // return response_json(["error"=>"Seu aplicativo está desatualizado. Por favor atualize seu aplicativo"], 401, false);

        $auth = new Auth("clientes");

        $sign = $auth->signIn($data->login, $data->senha, 0);

        if ($sign["error"]==false) {

            $user = $sign["user"];

            // Criar JWT Token
            $token = Jwt::generate(["guard"=>"clientes", "uid"=>$user->id]);

            if ($token) {

                $cliente = Cliente::findById($user->cliente_id);

                $response["usuario_id"] = (int)$user->id;
                $response["usuario_nome"] = $user->nome;
                $response["cliente_id"] = (int)$user->cliente_id;
                $response["cliente_nome"] = $cliente->razao;
                $response["suprimento"] = (int)$cliente->suprimento;
                $response["access_token"] = $token;
                $response["token_type"] = "bearer";
                $response["expires_in"] = Jwt::decode($token)->exp;

                $this->log->app("LOGIN", [
                    "user"=>$user->id,
                    "tabela"=>"clientes",
                ]);

                return response_json($response, 200, false);

            } else {

                return response_json(["error"=>"Houve um erro ao criar as credenciais"], 401, false);
                // return response_json(["message"=>"Houve um erro ao criar as credenciais"], 401);
            }

        } else {

            $message = is_int($sign["message"]) ? (new Message())->getText($sign["message"]) : $sign["message"];
            return response_json(["error"=>$message], 401, false);
            // return response_json(["message"=>$message], 401);
        }
    }

    public function tecnico()
    {

        $data = new Data();
        $data->json();
        $data->required(["login", "senha"]);

        if (APP_VERSION)
            $data->required("versao");

        if (APP_VERSION && ($data->versao != APP_VERSION))
            return response_json(["error"=>"Seu aplicativo está desatualizado. Por favor atualize seu aplicativo"], 401, false);

        $auth = new Auth("tecnicos");

        $sign = $auth->signIn($data->login, $data->senha, 0);

        if ($sign["error"]==false) {

            $user = $sign["user"];

            // Criar JWT Token
            $token = Jwt::generate(["guard"=>"tecnicos", "uid"=>$user->tecnico_id]);

            if ($token) {

                $response["tecnico_id"] = (int)$user->tecnico_id;
                $response["tecnico_nome"] = $user->tecnico_nome;
                $response["abrir_chamado"] = $user->abrir_chamado;
                $response["access_token"] = $token;
                $response["token_type"] = "bearer";
                $response["expires_in"] = Jwt::decode($token)->exp;

                $this->log->app("LOGIN", [
                    "user"=>$user->tecnico_id,
                    "tabela"=>"tecnicos",
                ]);

                return response_json($response, 200, false);

            } else {

                return response_json(["error"=>"Houve um erro ao criar as credenciais"], 401, false);
                // return response_json(["message"=>"Houve um erro ao criar as credenciais"], 401);
            }

        } else {

            $message = is_int($sign["message"]) ? (new Message())->getText($sign["message"]) : $sign["message"];
            return response_json(["error"=>$message], 401);
            // return response_json(["message"=>$message], 401);
        }
    }

}

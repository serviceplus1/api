<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Data;
use App\Core\Jwt;
use App\Models\Produtividade;
use App\Models\Tecnico;

class TecnicoController extends Controller
{

    public function __construct($router)
    {
        parent::__construct($router);

        $this->auth = new Auth("tecnicos");

        if (parent::loggedApi()===false)
            unauthorized();

        $this->auth->checkGuard("tecnicos");
    }

    public function me()
    {
        return response_json($this->auth->user());
    }

    public function logout()
    {
        return response_json(['message' => 'Você foi desconectado']);
    }

    public function refresh()
    {

        $auth = $this->auth->auth();

        // Criar JWT Token
        $token = Jwt::generate(["guard"=>"tecnicos", "uid"=>$auth->uid]);

        if ($token) {

            $response["access_token"] = $token;
            $response["expires_in"] = Jwt::decode($token)->exp;

            return response_json($response);

        } else {

            return response_json(["error"=>"Houve um erro ao criar as credenciais"], 401);
            // return response_json(["message"=>"Houve um erro ao criar as credenciais"], 401);
        }
    }

    public function update()
    {

        $data = new Data();
        $data->json();
        $data->remove(["tecnico_id", "password"]);

        $tecnico_id = $this->auth->user()->tecnico_id;

        if ($tecnico_id) {

            if (!empty($data->all())) {

                $update = Tecnico::where('tecnico_id', $tecnico_id)->update($data->all());

                if ($update) {

                    return response_json(['success' => 'Técnico alterado com sucesso'], 200);
                    // return response_json(['message' => 'Técnico alterado com sucesso'], 200);

                } else {

                    return response_json(['error'=>'Erro ao alterar técnico'], 400);
                    // return response_json(['message'=>'Erro ao alterar técnico'], 400);
                }

            } else {

                return response_json(['error'=>'Não foram passados dados para atualização'], 400);
                // return response_json(['message'=>'Técnico não encontrado'], 400);

            }

        } else {

            return response_json(['error'=>'Técnico não encontrado'], 400);
            // return response_json(['message'=>'Técnico não encontrado'], 400);

        }
    }

}

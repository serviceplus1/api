<?php
namespace App\Core;

use App\Core\View;
use App\Core\Auth;
use App\Core\Cors;
use App\Core\Csrf;
use App\Core\Data;
use App\Core\Log;
use App\Core\Message;
use App\Core\Redirect;
use CoffeeCode\Router\Router;

abstract class Controller
{
    /** @var View  */
    public $view;

    /** @var Log  */
    public $log;

    /** @var Router */
    public $router;

    /** @var Auth */
    public $auth;

    /** @var Message */
    public $message;

    /** @var Session */
    public $session;

    /** @var Config */
    public $authconfig;

    /** @var Csrf */
    public $csrf;

    /** @var Cache */
    public $cache;

    /** @var Cors */
    public $cors;

    public function __construct($router)
    {

        // header('Access-Control-Allow-Origin: *');
        // header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
        // header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
        // header('Content-Type: application/json');

        // $method = $_SERVER['REQUEST_METHOD'];
        // if ($method == "OPTIONS") {
        //     header('Access-Control-Allow-Origin: *');
        //     header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
        //     header("HTTP/1.1 200 OK");
        //     die();
        // }

        $this->router = $router;

        $this->cors = new Cors();
        $this->cors->enable();

        $this->view = new View();
        $this->view->addData(["router" => $this->router]);

        $this->log = new Log();

        $this->auth = new Auth();

        $this->message = new Message();

        $this->session = new Session();

        $this->authconfig = Config::get("auth");

        $this->csrf = new Csrf();

        $this->cache = new Cache();

        // Gravar Log de entrada
        $data = new Data();
        $data->json(false);
        $this->log->app("Request", [
            "request"=> $_REQUEST,
            "body"=> $data->all(),
        ]);
    }

    public function ajaxResponse(string $param, array $values): string
    {
        return json_encode([$param => $values]);
    }

    protected function loggedApi()
    {
        if ($this->auth->auth()===false)
            unauthorized();
    }

    protected function logged() : bool
    {

        if ($this->auth->auth()===false) {

            $url_atual = (new Redirect())->uri();

            $this->session->set("url_redirect", $url_atual);

            $this->message->flash(5);
            $this->router->redirect("login");
        }

        if ( !empty($this->authconfig["auth_connection"])) {
            DB::setConnection( $this->authconfig["auth_connection"] );
        }

        if ($this->cache->has("user")) {

            $this->user = $this->cache->user;
        }
        elseif ($this->session->has("user")) {

            $this->user = $this->session->user;
        }
        else {

            $this->user = $this->auth->user();

            // Guardar em cache ou sessão
            if (Config::get("redis.save")==true) {

                $this->session->unset("user");
                $this->cache->set("user", $this->user);

            } else {

                $this->session->set("user", $this->user);
            }
        }

        $this->id_empresa = $this->user->id_empresa;

        // Dados Padrão na View
        $this->view->addData([
            "auth" => $this->auth,
            "user" => $this->user,
            "message" => $this->message,
            "session" => $this->session,
            "title_site" => TITLE_SITE,
            "pagename" => null,
            "pageicon" => null,
            "pageinfo" => null,
        ]);

        return true;
    }


}

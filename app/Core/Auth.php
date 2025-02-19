<?php
namespace App\Core;

use \App\Core\Password;
use \App\Core\Session;
use \App\Core\Cookie;
use \App\Core\Config;
use \App\Core\Message;
use \App\Core\Cache;
use \App\Core\DB;
use \App\Core\Jwt;
use \App\Core\BasicAuth;
use \App\Core\ApiKey;
use App\Models\Usuario;
use \Respect\Validation\Validator;

class Auth {

    protected $guard;
    protected $driver;
    protected $name;
    protected $session;
    protected $cfg_session;
    protected $cookie;
    private $message;
    private $redirect;
    private $cache;
    public $permissoes;
    public $erro;

    public function __construct($guard=null)
    {

        $this->cfg_session = Config::get("session");

        $this->name = $this->cfg_session["prefix"];

        /** @var Session */
        $this->session = new Session();

        /** @var Cookie */
        $this->cookie = new Cookie();

        /** @var Message */
        $this->message = new Message();

        /** @var Redirect */
        $this->redirect = new Redirect();

        /** @var Cache */
        $this->cache = new Cache();

        $this->setGuard($guard);

    }

    public function setGuard($guard=null)
    {

        $this->config = Config::get("auth");
        $guards = Config::get("auth.guards");

        $this->guard = $guard ?: ($this->session->has('guard') ? $this->session->guard : $this->config["auth_default"]);

        if (isset($guards[$this->guard])) {

            $this->id = $guards[$this->guard]['id'];
            $this->driver = $guards[$this->guard]['driver'];
            $this->table = $guards[$this->guard]['table'];
            $this->login = $guards[$this->guard]['login'];
            $this->password = $guards[$this->guard]['password'];
            $this->status = $guards[$this->guard]['status'];
            $this->permissoes = $guards[$this->guard]['permissoes']['table'];

        } else {

            return ["error"=>true, "message"=>"This guard is not valid"];
        }
    }

    public function getGuard()
    {
        return $this->guard;
    }

    public function signIn($login, $pass, $manter)
    {

        // Verificar tipo de login
        if ($this->config["guards"][$this->guard]["types"]["login"]=="email") {
            if (Validator::email()->validate($login)==false) {
                return ["error"=>true, "message"=>20];
                // $this->erro = 20;
                // return false;
            }
        }

        $login = filter_var($login, FILTER_DEFAULT);
        $pass = filter_var($pass, FILTER_DEFAULT);

        if (!$login || !$pass) {

            return ["error"=>true, "message"=>4];
            // $this->erro = 4;
            // return false;
        }

        if ( !empty($this->config["auth_connection"])) {
            DB::setConnection( $this->config["auth_connection"] );
        }

        $User = DB::table($this->table)->find($login, $this->login);

        if ($User) {

            if ((new Password())->verify($pass, $User->{$this->password})) {

                if ($this->status) {

                    $active_val = $this->config["guards"][$this->guard]["types"]["status"]["active_val"];

                    if ($User->{$this->status}==$active_val) {

                        if ($this->driver=="session") {

                            $this->register($User, $manter);
                            return true;
                        }
                        else {

                            return ["error"=>false, "user"=>$User];
                            // return true;
                        }

                    } else {

                        return ["error"=>true, "message"=>2];
                        // $this->erro = 2;
                        // return false;
                    }

                } else {

                    if ($this->driver=="session") {

                        $this->register($User, $manter);
                        return true;
                    }
                    else {

                        return ["error"=>false, "user"=>$User];
                        // return true;
                    }

                }

            } else {

                return ["error"=>true, "message"=>3];
                // $this->erro = 2;
                // return false;
            }

        } else {

            return ["error"=>true, "message"=>1];
            // $this->erro = 2;
            // return false;
        }
    }

    private function register($User, $manter=0)
    {

        // Encontrar Permissões
        $permissoes = Usuario::findById($User->id)->permissoes;

        $guardar = serialize([
            'id'=>$User->{$this->id},
            'guard'=>$this->guard,
            'logado'=>true,
            'permissoes'=>$permissoes,
        ]);

        $this->session->set($this->name, $guardar);

        // CRIANDO DADOS DE COOKIES
        if ($manter==1) {

            $this->cookie->set($this->name, $guardar);

        } else {

            $this->cookie->unset($this->name);
        }
    }

    public function auth()
    {

        if ($this->driver=="session") {

            if ($this->session->has($this->name)===false&&$this->cookie->has($this->name)===false) {

                $this->erro = 5;
                return false;

            } else {

                $auth = $this->session->has($this->name)
                    ? unserialize($this->session->{$this->name})
                    : (
                        $this->cookie->has($this->name)
                        ? unserialize($this->cookie->{$this->name})
                        : false
                    );

                if ($auth==false) {

                    return false;
                }

                return (object)unserialize($this->session->{$this->name});
            }

        }
        else {

            if (strtolower($this->driver)=="apikey") {

                return ApiKey::validate();
            }

            $headers = getallheaders();

            if (!isset($headers["Authorization"]))
                return false;

            list($type, $token) = explode(" " , $headers["Authorization"]);

            if (!$type || !$token)
                return false;

            if (strtolower($this->driver)=="jwt") {

                if(strtolower($type)!=="bearer")
                    return false;

                if (Jwt::validate($token)) {

                    return Jwt::decode($token);

                } else {

                    return false;
                }
            }
            elseif (strtolower($this->driver)=="basic") {

                if (strtolower($type)!=="basic")
                    return false;

                return BasicAuth::validate($token);

            }
        }

        return false;
    }

    public function checkGuard($guard)
    {
        $auth = $this->auth();

        if ($auth->guard !== $guard)
            unauthorized();
    }

    public function logout()
    {

        $this->session->unset($this->name);
        $this->cookie->unset($this->name);
        return !$this->auth();
    }

    public function user()
    {
        $auth = $this->auth();

        if ($this->driver=="session") {

            $User = Usuario::findById($auth->{$this->id});
        }
        elseif ($this->driver=="jwt") {

            $this->setGuard($auth->guard);

            $User = DB::table($this->table)->find($auth->uid, $this->id);
        }

        unset($User->{$this->password});
        return $User;
    }

    public function allow($name, $redirect=null)
    {

        $permissions = unserialize(unserialize($this->session->{$this->name})["permissoes"]);

        if (!empty($permissions)) {

            if ($this->cache->has("permissoes")) {

                $permissoes = $this->cache->permissoes;

            } elseif ($this->session->has("permissoes")) {

                $permissoes = $this->sesison->permissoes;

            } else {

                $permissoes = DB::table($this->permissoes)->whereIn($permissions)->pluck("permissao");

                // Guardar em cache ou sessão
                if (Config::get("redis.save")==true) {

                    $this->session->unset("permissoes");
                    $this->cache->set("permissoes", $permissoes);

                } else {

                    $this->session->set("permissoes", $permissoes);
                }
            }

            if (in_array($name, $permissoes)) {
                return true;
            }
        }

        if ($redirect) {

            $this->message->flash(7);
            $this->redirect->go( $redirect );
        }

        return false;
    }

    public function renew($permissions)
    {

        $user = $this->user();

        $guardar = serialize([
            'id'=>$user->{$this->id},
            'guard'=>$this->guard,
            'logado'=>true,
            'permissoes'=>$permissions,
        ]);

        if ($this->cookie->has($this->name)) {

            $this->cookie->set($this->name, $guardar);
        }

        $this->session->set($this->name, $guardar);
    }

}

<?php
namespace App\Core;

use \App\Core\Session;

class Message
{

    private $message;

    /** @var Session */
    private $session;

    private function get($id, $key=null)
    {

        $messages = [
                         1=>['class'=>'danger',  'icon'=>'exclamation-circle',   'alter'=>'Erro',    'text'=>'Usuário/Senha Incorretos'], //'Usuario não cadastrado'
                         2=>['class'=>'warning', 'icon'=>'exclamation-triangle', 'alter'=>'Atenção', 'text'=>'Usuario não ativo no sistema'],
                         3=>['class'=>'danger',  'icon'=>'exclamation-circle',   'alter'=>'Erro',    'text'=>'Usuário/Senha Incorretos'], //Senha Incorreta
                         4=>['class'=>'warning', 'icon'=>'exclamation-triangle', 'alter'=>'Atenção', 'text'=>'Digite seu usuário e senha'],
                         5=>['class'=>'warning', 'icon'=>'exclamation-triangle', 'alter'=>'Atenção', 'text'=>'Você não está conectado ao sistema'],
                         6=>['class'=>'warning', 'icon'=>'exclamation-triangle', 'alter'=>'Atenção', 'text'=>'Você foi desconectado do sistema'],
                         7=>['class'=>'warning', 'icon'=>'exclamation-triangle', 'alter'=>'Atenção', 'text'=>'Você não tem permissão pra visualizar esta página'],
                         8=>['class'=>'success', 'icon'=>'check-circle',         'alter'=>'Sucesso', 'text'=>'Registro Salvo com sucesso'],
                         9=>['class'=>'danger',  'icon'=>'exclamation-circle',   'alter'=>'Erro',    'text'=>'Houve um erro ao salvar registro'],
                        10=>['class'=>'success', 'icon'=>'check-circle',         'alter'=>'Sucesso', 'text'=>'Registro alterado com sucesso'],
                        11=>['class'=>'danger',  'icon'=>'exclamation-circle',   'alter'=>'Erro',    'text'=>'Houve um erro ao alterar registro'],
                        12=>['class'=>'success', 'icon'=>'check-circle',         'alter'=>'Sucesso', 'text'=>'Registro(s) excluido(s) com sucesso'],
                        13=>['class'=>'danger',  'icon'=>'exclamation-circle',   'alter'=>'Erro',    'text'=>'Houve um erro ao excluir o(s) registro(s)'],
                        14=>['class'=>'success', 'icon'=>'check-circle',         'alter'=>'Sucesso', 'text'=>'Status alterado(s) com sucesso'],
                        15=>['class'=>'danger',  'icon'=>'exclamation-circle',   'alter'=>'Erro',    'text'=>'Não é possivel alterar o status'],
                        16=>['class'=>'warning', 'icon'=>'exclamation-triangle', 'alter'=>'Atenção', 'text'=>'Não foi possivel alterar o status de %1. %2 com sucesso'],
                        17=>['class'=>'warning', 'icon'=>'exclamation-triangle', 'alter'=>'Atenção', 'text'=>'Não foi possível excluir %1. %2 com sucesso'],
                        18=>['class'=>'danger',  'icon'=>'exclamation-circle',   'alter'=>'Erro',    'text'=>'Arquivo com Extensão Inválida'],
                        19=>['class'=>'danger',  'icon'=>'exclamation-circle',   'alter'=>'Erro',    'text'=>'Houve um erro ao subir o arquivo'],
                        20=>['class'=>'danger',  'icon'=>'exclamation-circle',   'alter'=>'Erro',    'text'=>'O login deve ser do tipo e-mail'],
                    ];

        if ($key)
            $this->message = $messages[$id][$key];
        else
            $this->message = $messages[$id];

        return $this;
    }

    public function getText($id)
    {
        $this->get($id);
        return $this->message["text"];
    }

    public function class()
    {
        return $this->message["class"];
    }

    public function icon()
    {
        return $this->message["icon"];
    }

    public function alter()
    {
        return $this->message["alter"];
    }

    public function text()
    {
        return $this->message["text"];
    }

    public function flash($index=null, $type=null, $icon=null)
    {
        $this->session = new Session();

        if ($index) {

            $this->session->set("flash", [
                "index" => $index,
                "class" => $type,
                "icon" => $icon,
            ]);

            return null;
        }

        if ($this->session->has("flash")) {

            $flash = $this->session->flash;
            $this->session->unset("flash");

            if (is_array($flash->index)) {

                $msgs = '';

                foreach ($flash->index as $f) {

                    $msgs .= $this->{$f["class"]}($f["text"]);
                }

                return $msgs;

            } else {

                return $this->alert(
                    $flash->index,
                    $flash->class,
                    $flash->icon
                );
            }
        }
    }

    public function alert($index, $class=null, $icon=null, $alter=null)
    {

        if (is_int($index)) {

            $this->get($index);

            $text  = $this->text();
            $class = $this->class();
            $icon  = $this->icon();
            $alter = $this->alter();
        }
        else {

            $text = $index;
        }

        if ($icon==true) {

            if ($class) $icon = self::icons($class);
            else        $icon = null;
        }

        /* Retorno Bootstrap */
        $html = '<div class="alert alert-'.$class.'" role="alert">';
            if (!is_null($icon))  $html .= '<span class="fa fa-fw fa-'.$icon.'" aria-hidden="true"></span>';
            if (!is_null($alter)) $html .= '<span class="sr-only">'.$alter.':</span>';
            $html .= '<button type="button" class="close" data-dismiss="alert">×</button>';
            $html .= ' '.$text;
        $html .= '</div>';

        echo $html;
    }

    public function success($text, $icon="check-circle", $alter="Sucesso")
    {
        return $this->alert($text, "success", $icon, $alter);
    }

    public function warning($text, $icon="exclamation-triangle", $alter="Atenção")
    {
        return $this->alert($text, "warning", $icon, $alter);
    }

    public function danger($text, $icon="exclamation-circle", $alter="Erro")
    {
        return $this->alert($text, "danger", $icon, $alter);
    }

    public function info($text, $icon="info-circle", $alter="Informação")
    {
        return $this->alert($text, "info", $icon, $alter);
    }

    public function primary($text, $icon=null, $alter=null)
    {
        return $this->alert($text, "primary", $icon, $alter);
    }

    public function secondary($text, $icon=null, $alter=null)
    {
        return $this->alert($text, "secondary", $icon, $alter);
    }

    public function light($text, $icon=null, $alter=null)
    {
        return $this->alert($text, "light", $icon, $alter);
    }

    public function dark($text, $icon=null, $alter=null)
    {
        return $this->alert($text, "dark", $icon, $alter);
    }

    protected function icons($type)
    {

        $icons = [
                    "success" => "check-circle",
                    "warning" => "exclamation-triangle",
                    "danger" => "exclamation-circle",
                    "info" => "info-circle",
        ];
        return $icons[$type];
    }



}

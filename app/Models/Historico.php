<?php
namespace App\Models;

use App\Core\Model;

class Historico extends Model
{

    static $table = "historico";
    static $alias = "h";
    static $primaryKey = "id";

    public static function actions($act, $key=null)
    {

        $actions =[
            "abrir" => ["assunto"=>"Chamado Criado", "mensagem"=>"Novo Chamado: ", "publico"=>1, "icon"=>"fas fa-plus", "class"=>"primary"],
            "encaminhar" => ["assunto"=>"Chamado Encaminhado", "mensagem"=>"O chamado foi encaminhado para o técnico ", "publico"=>0, "icon"=>"fas fa-user-cog", "class" =>"success"],
            "tecnico" => ["assunto"=>"Troca de Técnico", "mensagem"=>"O chamado teve o técnico trocado para ", "publico"=>0, "icon"=>"fas fa-user-friends", "class"=>"info"],
            "transito" => ["assunto"=>"Chamado em Trânsito", "mensagem"=>"O Técnico está à caminho.", "publico"=>1, "icon"=>"fas fa-car-side", "class" =>"info"],
            "checkin" => ["assunto"=>"Check-In", "mensagem"=>"O chamado teve seu atendimento iniciado.", "publico"=>0, "icon"=>"fas fa-play", "class"=>"success"],
            "agendar" => ["assunto"=>"Chamado Agendado", "mensagem"=>"O chamado teve o atendimento agendado para ", "publico"=>0, "icon"=>"far fa-calendar-alt", "class"=>"orange"],
            "pendente" => ["assunto"=>"Chamado Pendente", "mensagem"=>"O chamado teve uma pendência ", "publico"=>0, "icon"=>"fas fa-exclamation-circle", "class"=>"danger"],
            "finalizar" => ["assunto"=>"Chamado Finalizado", "mensagem"=>"O chamado foi encerrado.", "publico"=>1, "icon"=>"fas fa-check", "class"=>"success"],
            "cancelar" => ["assunto"=>"Chamado Cancelado", "mensagem"=>"O chamado foi cancelado", "publico"=>0, "icon"=>"fas fa-times", "class"=>"danger"],
            "avaliar" => ["assunto"=>"Avaliação", "mensagem"=>"O chamado foi avaliado: ", "publico"=>0, "icon"=>"fas fa-star", "class"=>"warning"],
            "comentar" => ["assunto"=>"Novo Comentário", "mensagem"=>"Novo comentário: ", "publico"=>0, "icon"=>"fas fa-comments", "class"=>"secondary"],
            "reabrir" => ["assunto"=>"Chamado Reaberto", "mensagem"=>"Chamado Reaberto: ", "publico"=>1, "icon"=>"fas fa-undo-alt", "class"=>"primary"],
            "alterar_equipamento" => ["assunto"=>"Equipamento Alterado", "mensagem"=>"O equipamento foi alterado para o ", "publico"=>0, "icon"=>"fas fa-sync-alt", "class"=>"primary"],
            "inserir_equipamento" => ["assunto"=>"Equipamento Inserido", "mensagem"=>"O equipamento foi inserido: ", "publico"=>0, "icon"=>"fas fa-plus-square", "class"=>"primary"],
            "excluir_equipamento" => ["assunto"=>"Equipamento Excluido", "mensagem"=>"O equipamento foi excluido", "publico"=>0, "icon"=>"fas fa-minus-square", "class"=>"danger"],
            "separar_suprimento" => ["assunto"=>"Chamado em Separação", "mensagem"=>"O chamado teve a separação dos suprimentos iniciada por ", "publico"=>0, "icon"=>"fas fa-exchange-alt", "class"=>"warning"],
            "trocar_separacao" => ["assunto"=>"Troca de Responsável", "mensagem"=>"O chamado teve o responsável pela separação trocado para ", "publico"=>0, "icon"=>"fas fa-sync", "class"=>"info"],
            "despachar_suprimento" => ["assunto"=>"Chamado foi Despachado", "mensagem"=>"O chamado foi despachado ", "publico"=>0, "icon"=>"fas fa-share-square", "class"=>"success"],
            "quantidade_suprimento" => ["assunto"=>"A quantidade foi alterada", "mensagem"=>"A quantidade do item {{suprimento}} foi alterada de {{qtde}} para {{qtde_nova}} com a justificativa: {{ justificativa}}", "publico"=>0, "icon"=>"fas fa-sync", "class"=>"info"],
            "separacao_finalizada" => ["assunto"=>"A separação foi finalizada", "mensagem"=>"A separação dos itens foi finalizada e está aguardando a coleta do técnico: ", "publico"=>0, "icon"=>"fas fa-sync", "class"=>"success"],
            "coletado" => ["assunto"=>"O pedido foi coletado e está com o técnico", "mensagem"=>"O pedido já foi coletado e está com o técnico aguardando a entrega.", "publico"=>0, "icon"=>"fas fa-clock", "class"=>"info"],
        ];

        if ($key)
            return $actions[$act][$key];
        else
            return $actions[$act];

    }

}

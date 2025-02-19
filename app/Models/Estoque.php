<?php

namespace App\Models;

use App\Core\DB;
use App\Core\Model;

class Estoque extends Model
{

    private static function createMov($chamado_id, $local_id, $tipo)
    {

        // Criar Movimentação
        $movimentacao = DB::table("estoque_movimentacao")->insert([
            "tipo" => $tipo,
            "local_id" => $local_id,
            "descricao" => "Chamado ".$chamado_id,
        ]);

        if ($movimentacao->result==false)
            return response_json(["error"=>'Houve um erro ao tentar baixar o estoque'], 400);

        $id_movimentacao = $movimentacao->id;

        // Adicionar Itens: Peças
        $pecas = ChamadoPeca::where("chamado_id", $chamado_id)
            ->where("baixado", 0)
            ->get();

        if (nreg($pecas)>0) {

            // Campos  que serão inseridos na tabela de movimentacao_item
            $columns = ["movimentacao_id", "peca_id", "quantidade", "valor", "tipo"];

            // Pegar todos os valores à serem inseridos
            foreach ($pecas as $p) {
                $values[] = [$id_movimentacao, $p->peca_id, $p->quantidade, $p->valor, $tipo[0]];
            }

            // Inserir Itens na Movimentação
            DB::table("estoque_movimentacao_item")->insertMany($columns, $values);
        }

        // Adicionar Itens: Suprimentos
        $suprimentos = ChamadoSuprimento::where("chamado_id", $chamado_id)
            ->where("baixado", 0)
            ->get();

        if (nreg($suprimentos)>0) {

            // Campos  que serão inseridos na tabela de movimentacao_item
            $columns = ["movimentacao_id", "suprimento_id", "quantidade", "valor", "tipo"];

            // Pegar todos os valores à serem inseridos
            foreach ($suprimentos as $s) {
                $values[] = [$id_movimentacao, $s->suprimento_id, $s->quantidade_enviada, $s->valor, $tipo[0]];
            }

            // Inserir Itens na Movimentação
            DB::table("estoque_movimentacao_item")->insertMany($columns, $values);
        }

        return $id_movimentacao;

    }


    /**
     * Executar a tarefa de dar saida no estoque
     *
     * @param [int] $id_movimentacao
     * @param [string] $tipo: saida/entrada
     * @param [int] $local_id
     * @return void
     */
    public static function saida($chamado_id, $local_id)
    {

        $id = self::createMov($chamado_id, $local_id, "saida");

        if (!$id)
            return response_json(["error"=>'Houve um erro ao tentar baixar o estoque'], 400);


        // Pegar todos os items que foram inseridos
        $items = DB::table("estoque_movimentacao_item")->where("movimentacao_id", $id)->get();

        foreach ($items as $i) {

            if ($i->peca_id) {

                // Atualizar Estoque do Local
                self::atualizaPeca($local_id, $i->peca_id, $i->quantidade, "saida");

            } elseif ($i->suprimento_id) {

                // Atualizar Estoque do Local
                self::atualizaSuprimento($local_id, $i->suprimento_id, $i->quantidade, "saida");
            }
        }
    }

    /**
     * Executar a tarefa de dar entrada no estoque
     *
     * @param [int] $id_movimentacao
     * @param [string] $tipo: saida/entrada
     * @param [int] $local_id
     * @return void
     */
    public static function entrada($chamado_id, $local_id)
    {

        $id = self::createMov($chamado_id, $local_id, "entrada");

        if (!$id)
            return response_json(["error"=>'Houve um erro ao tentar dar entrada no estoque'], 400);


        // Pegar todos os items que foram inseridos
        $items = DB::table("estoque_movimentacao_item")->where("movimentacao_id", $id)->get();

        foreach ($items as $i) {

            if ($i->peca_id) {

                // Atualizar Estoque do Local
                self::atualizaPeca($local_id, $i->peca_id, $i->quantidade, "entrada");

            } elseif ($i->suprimento_id) {

                // Atualizar Estoque do Local
                self::atualizaSuprimento($local_id, $i->suprimento_id, $i->quantidade, "entrada");
            }
        }
    }

    /**
     * Atualizar a quantidade de peças no estoque
     *
     * @param [int] $local_id
     * @param [int] $id
     * @param [float] $quantidade
     * @param [string] $tipo
     * @return void
     */
    private static function atualizaPeca($local_id, $id, $quantidade, $tipo)
    {

        // Ver se a peça já existe no estoque
        $estoque = DB::table("estoque_peca")
            ->where("local_id", $local_id)
            ->where("peca_id", $id)
            ->get();


        if ($estoque) {

            // Se sim atualizar quantidade
            $novo_estoque = $tipo=="entrada" ? $estoque[0]->quantidade + $quantidade : $estoque[0]->quantidade - $quantidade;

            // Atualizar estoque
            DB::table("estoque_peca")
                ->where("id", $estoque[0]->id)
                ->update(["quantidade" => $novo_estoque]);

        } else {

            $novo_estoque = $tipo=="entrada" ? $quantidade : -$quantidade;

            // Insere a peça no estoque
            DB::table("estoque_peca")->insert([
                "peca_id" => $id,
                "local_id" => $local_id,
                "quantidade" => $novo_estoque
            ]);
        }
    }

    /**
     * Atualizar o estoque de suprimento
     *
     * @param [int] $local_id
     * @param [int] $id
     * @param [float] $quantidade
     * @param [string] $tipo
     * @return void
     */
    private static function atualizaSuprimento($local_id, $id, $quantidade, $tipo)
    {

        // Ver se o suprimento já existe no estoque
        $estoque = DB::table("estoque_suprimento")
            ->where("local_id", $local_id)
            ->where("suprimento_id", $id)
            ->get();

        if ($estoque) {

            // Se sim atualizar quantidade
            $novo_estoque = $tipo=="entrada" ? $estoque[0]->quantidade + $quantidade : $estoque[0]->quantidade - $quantidade;

            // Atualizar estoque
            DB::table("estoque_suprimento")
                ->where("id", $estoque[0]->id)
                ->update(["quantidade" => $novo_estoque]);

        } else {

            $novo_estoque = $tipo=="entrada" ? $quantidade : -$quantidade;

            // Insere o suprimento no estoque
            DB::table("estoque_suprimento")->insert([
                "suprimento_id" => $id,
                "local_id" => $local_id,
                "quantidade" => $novo_estoque
            ]);
        }
    }

}

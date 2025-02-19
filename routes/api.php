<?php

$router->namespace("\App\Controllers");

$router->group("clientes");
    $router->post("/login", "LoginController:cliente", "api.login.cliente");
    $router->get("/refresh", "ClienteController:refresh", "api.cliente.refresh");
    $router->get("/me", "ClienteController:me", "api.cliente.me");
    $router->get("/logout", "ClienteController:logout", "api.cliente.logout");
    $router->get("/", "ClienteController:list", "api.cliente.list");
    $router->post("/", "ClienteController:create", "api.cliente.create");
    $router->put("/", "ClienteController:update", "api.cliente.update");

$router->group("tecnicos");
    $router->post("/login", "LoginController:tecnico", "api.login.tecnico");
    $router->get("/refresh", "TecnicoController:refresh", "api.tecnico.refresh");
    $router->get("/me", "TecnicoController:me", "api.tecnico.me");
    $router->get("/logout", "TecnicoController:logout", "api.tecnico.logout");
    $router->put("/", "TecnicoController:update", "api.tecnico.update");

$router->group("trajeto");
    $router->post("/iniciar", "TrajetoController:start", "api.trajeto.start");
    $router->post("/finalizar", "TrajetoController:finish", "api.trajeto.finish");

$router->group("formas");
    $router->get("/", "FormaController:list", "api.forma.list");
    $router->post("/", "FormaController:insert", "api.forma.insert");

$router->group("categorias");
    $router->get("/", "DespesaController:categorias", "api.despesa.categoria.list");
    $router->post("/", "DespesaController:insertCategoria", "api.despesa.categoria.insert");

$router->group("despesas");
    $router->get("/", "DespesaController:list", "api.despesa.list");
    $router->get("/{id_despesa}", "DespesaController:show", "api.despesa.show");
    $router->post("/", "DespesaController:insert", "api.despesa.insert");
    $router->put("/", "DespesaController:update", "api.despesa.update");
    $router->delete("/", "DespesaController:delete", "api.despesa.delete");

$router->group("rotas");
    $router->get("/", "RotaController:list", "api.rota.list");
    $router->post("/", "RotaController:insert", "api.rota.insert");

$router->group("pontos");
    $router->get("/", "PontoController:list", "api.ponto.list");
    $router->post("/", "PontoController:insert", "api.ponto.insert");

$router->group("pecas");
    $router->get("/", "PecaController:list", "api.peca.list");
    $router->post("/", "PecaController:insert", "api.peca.insert");

$router->group("servicos");
    $router->get("/", "ServicoController:list", "api.servico.list");

$router->group("suprimentos");
    $router->get("/{maquina_id}", "SuprimentoController:list", "api.suprimento.list");

$router->group("equipamentos");
    $router->get("/", "EquipamentoController:list", "api.equipamento.list");
    $router->get("/status", "EquipamentoStatusController:list", "api.equipamento.status.list"); // Status dos equipamentos

$router->group("equipamento");
    $router->get("/", "EquipamentoController:show", "api.equipamento.show");

$router->group("avaliacoes");
    $router->get("/", "AvaliacaoController:list", "api.avaliacao.list");
    $router->get("/{id}", "AvaliacaoController:show", "api.avaliacao.show");
    $router->put("/{id}", "AvaliacaoController:update", "api.avaliacao.update");
    $router->get("/teste", "AvaliacaoController:teste", "api.avaliacao.teste");

$router->group("instalacao");
    $router->post("/", "InstalacaoController:insert", "api.instalacao.insert");

$router->group("chamados");
    $router->get("/", "ChamadoController:list", "api.chamado.list");
    $router->get("/{id}", "ChamadoController:show", "api.chamado.show");
    $router->post("/new", "ChamadoController:insert", "api.chamado.insert");
    $router->put("/", "ChamadoController:update", "api.chamado.update");
    $router->put("/pendencia", "ChamadoController:pendency", "api.chamado.pendency");
    $router->put("/iniciar", "ChamadoController:start", "api.chamado.start");
    $router->put("/checkin", "ChamadoController:checkin", "api.chamado.checkin");
    $router->put("/equipamento", "ChamadoController:equipamento", "api.chamado.equipamento");
    $router->put("/cancelar", "ChamadoController:cancel", "api.chamado.cancel");
    $router->put("/finalizar", "ChamadoController:finish", "api.chamado.finish");
    $router->post("/upload", "ChamadoController:upload", "api.chamado.upload");
    $router->delete('/', "ChamadoController:delete", "api.chamado.delete");
    $router->get("/status", "ChamadoStatusController:list", "api.chamado.status.list");
    $router->get("/categorias", "ChamadoCategoriaController:list", "api.chamado.categoria.list");

$router->group("historico");
    $router->get("/", "HistoricoController:list", "api.historico.list");
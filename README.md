# apiserviceplus
API para comunicação entre o aplicativo e a aplicação web da Serviceplus
## Configurações
Para configurar a API é necessário acessar o arquivo `config/app.php` e alterar as váriaveis:

    <?php
        $path = "apiserviceplus"; // Diretório de instalação

        $protocol = "https";

        $token_client = ""; // Número de Token do cliente serviceplus

        $url_app = "https://app.serviceplus.com.br"; // Endereço da aplicação web

        $version = 2; // Versão da API
    ?>

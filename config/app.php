<?php
/**
 * Configuação da API
 */

$path = "nomedapasta";

$protocol = "https";

$token_client = "94257b599bcfb2d6c6e265fb68667f69340d2ef6";

$url_app = "https://app.serviceplus.com.br";

$version = 3;



/*****************
 *  NÂO ALTERAR  *
 *****************/

define("PATH_STORAGE", __DIR__."/../storage/");

define("PATH_BASE", !empty($path)?"/".$path:"");

define("URL_BASE", $protocol."://".$_SERVER["HTTP_HOST"].PATH_BASE);

define("URL_APP", $url_app);

define("APP_VERSION", $version);

define("TOKEN_CLIENT", $token_client);
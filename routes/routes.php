<?php
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Credentials: true');
// header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
// header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
// header('Access-Control-Max-Age: 86400');


/**
 * Routes by coffeecode/router
 */

use CoffeeCode\Router\Router;

$router = new Router(URL_BASE);


include "api.php";

/**
 * Group Error
 * This monitors all Router errors. Are they: 400 Bad Request, 404 Not Found, 405 Method Not Allowed and 501 Not Implemented
 */
$router->group("ooops")->namespace("\App\Controllers");
    $router->get("/{errcode}", "ErrorController:index");

/**
 * This method executes the routes
 */
$router->dispatch();

/*
 * Redirect all errors
 */
if ($router->error()) {

    response_json($router->error(), $router->error());

    // echo $router->error();

    // $router->redirect("error", ["errorcode" => $router->error()]);
}
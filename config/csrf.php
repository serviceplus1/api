<?php
    return [

        "secure" => true,

        "hash_name" => "csrf",

        "algo" => "sha256", // https://www.php.net/manual/pt_BR/function.hash-hmac-algos.php

        "data" => "csrf secure", // Message to be hashed

        "key" => bin2hex(random_bytes(32)),

        "output" => FALSE,
    ];
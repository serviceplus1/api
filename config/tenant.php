<?php
    return [

        'driver' => 'mysql',

        'host' => $_SESSION['tenant']->db_host ?? null,

        'database' => $_SESSION['tenant']->db_name ?? null,

        'username' => $_SESSION['tenant']->db_user ?? null,

        'password' => $_SESSION['tenant']->db_pass ?? null,

        'port' => '',

        'charset' => 'charset=utf8;',

    ];
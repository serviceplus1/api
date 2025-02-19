<?php
    return [

        'auth_connection' => '', // tenant

        'auth_default' => 'tecnicos',

        'guards' => [

            'tecnicos' => [
                'driver' => 'jwt', // session, jwt, basic
                'table' => 'tecnicos',
                'id' => 'tecnico_id',
                'login' => 'login',
                'password' => 'password',
                'status' => false, // field by status, false for ignore
                'types' => [
                    'login' => 'string', //type of login: email or string
                    'status' => [
                        "active_val" => 1,
                        "inactive_val" => 0,
                    ]
                ],
                'permissoes' => [
                    'table' => 'tab_usuario_permissao'
                ]
            ],

            'clientes' => [
                'driver' => 'jwt',
                'table' => 'clientes_usuarios',
                'id' => 'id',
                'login' => 'login',
                'password' => 'senha',
                'status' => 'status',
                'types' => [
                    'login' => 'string',
                    'status' => [
                        "active_val" => 1,
                        "inactive_val" => 0,
                    ]
                ],
                'permissoes' => [
                    'table' => false
                ]
            ],

        ],

    ];
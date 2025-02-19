<?php
return [

    'prefix' => 'adminservp',

    'driver' => 'file',

    'path' => __DIR__."/../storage/sessions",

    'cookie' => [

        'days' => '24',
        'path' => '/',

    ],
];
<?php

return [
    'roles' => [
        'authenticated-user'        => [],
        'guest'                     => [
            'authenticated-user'
        ],
    ],
    'permissions' => [
        'guest' => [
            'sign.in',
        ],
        'authenticated-user' => [
            'feedback',
            'home',
            'user.find',
            'user.search',
            'sign.out',
            'system.message',
        ],
    ],
];

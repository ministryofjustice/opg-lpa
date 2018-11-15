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
            'home',
            'user.feedback',
            'user.search',
            'sign.out',
            'system.message',
        ],
    ],
];

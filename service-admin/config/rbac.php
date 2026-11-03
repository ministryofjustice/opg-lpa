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
            'deletion-log.delete',
            'feedback',
            'home',
            'user.lpas',
            'shared-space.lpas',
            'shared-space.members',
            'search',
            'sign.out',
            'system.message',
        ],
    ],
];

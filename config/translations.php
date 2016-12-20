<?php

return array(

    /**
    * Routes group config
    */
    'routes' => [
        'prefix' => 'translations-manager',
        'middleware' => 'web',
    ],
    
    /**
     * Feature toggles
     */
    'features' => [
        'create_locales' => true,
        'delete_translations' => true,
        'truncate_translations' => true
    ],
    
    'drivers' => [
        'laravel' => [
            'enabled' => true,
            'exclude_groups' => [],
        ],
        'dimsav' => [
            'enabled' => true,
            'models' => [
                \App\User::class
            ]
        ]
    ]

);

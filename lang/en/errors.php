<?php

/*
|--------------------------------------------------------------------------
| Error / exception strings
|--------------------------------------------------------------------------
|
| `unauthorized` drives the in-app AuthorizationModal shown when an Inertia
| visit is denied (403). `status` drives the full-page ErrorPage.vue used for
| non-Inertia first loads, keyed by HTTP status code.
|
*/

return [
    'back_home' => 'Back to safety',
    'ok' => 'OK',

    'unauthorized' => [
        'title' => 'Access denied',
        'description' => 'You do not have access to this feature.',
    ],

    'status' => [
        403 => [
            'title' => 'Access denied',
            'description' => 'You do not have access to this feature.',
        ],
        404 => [
            'title' => 'Page not found',
            'description' => 'Sorry, the page you are looking for could not be found.',
        ],
        500 => [
            'title' => 'Server error',
            'description' => 'Whoops, something went wrong on our end.',
        ],
        503 => [
            'title' => 'Service unavailable',
            'description' => 'Sorry, we are doing some maintenance. Please check back soon.',
        ],
    ],
];

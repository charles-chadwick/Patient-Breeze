<?php

/*
|--------------------------------------------------------------------------
| Error / exception strings
|--------------------------------------------------------------------------
|
| `status` is keyed by HTTP status code and drives both the in-app ErrorModal
| shown on client-side Inertia visits and the full-page ErrorPage.vue used for
| non-Inertia first loads. `generic` is the fallback for any status without its
| own entry.
|
*/

return [
    'back_home' => 'Back to safety',
    'ok' => 'OK',

    'status' => [
        'generic' => [
            'title' => 'Something went wrong',
            'description' => 'An unexpected error occurred. Please try again.',
        ],
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

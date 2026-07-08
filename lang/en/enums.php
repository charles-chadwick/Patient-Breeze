<?php

/*
|--------------------------------------------------------------------------
| Enum display labels
|--------------------------------------------------------------------------
|
| Enum *values* are stored data and stay unchanged; only their human-facing
| labels are translated. Keyed by the backing value so both PHP
| (__('enums.user_role.Super Admin')) and Vue ($t('enums.user_role.' + name))
| can resolve a label from a value.
|
*/

return [
    'user_role' => [
        'Super Admin' => 'Super Admin',
        'Doctor' => 'Doctor',
        'Nurse' => 'Nurse',
        'Medical Assistant' => 'Medical Assistant',
        'Staff' => 'Staff',
    ],
];

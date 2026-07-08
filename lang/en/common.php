<?php

/*
|--------------------------------------------------------------------------
| Shared / reusable UI strings
|--------------------------------------------------------------------------
|
| Key convention: domain.section.element (e.g. users.index.title).
| Strings reused across more than one domain live here in `common`.
| Access from PHP with __('common.actions.save') and from Vue with
| $t('common.actions.save').
|
*/

return [
    'actions' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'select_placeholder' => 'Select…',
    ],

    'pagination' => [
        // :from, :to and :total are interpolated, :label names the record type.
        'summary' => 'Showing :from–:to of :total :label',
    ],

    'placeholders' => [
        'em_dash' => '—',
    ],
];

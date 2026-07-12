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
    'brand' => [
        'name' => 'Patient Breeze',
        'portal_name' => 'Patient Breeze Portal',
        'portal_label' => 'Patient Portal',
        'portal_tagline' => 'Your health, at your fingertips.',
    ],

    'actions' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'close' => 'Close',
        'confirm' => 'Confirm',
        'sign_out' => 'Sign Out',
        'sort' => 'Sort',
        'select_placeholder' => 'Select…',
    ],

    'labels' => [
        'sign_out' => 'Sign out',
        'yes' => 'Yes',
        'no' => 'No',
        'field' => 'Field',
    ],

    'confirm' => [
        'default_title' => 'Are you sure?',
    ],

    'a11y' => [
        'breadcrumb' => 'Breadcrumb',
    ],

    'avatar' => [
        'alt' => 'Avatar',
        'upload_title' => 'Click to upload avatar',
        'change_photo' => 'Change Photo',
        'upload_photo' => 'Upload Photo',
        'remove' => 'Remove',
        'hint' => 'JPG, PNG or GIF · Max 2 MB',
    ],

    'pagination' => [
        // :from, :to and :total are interpolated, :label names the record type.
        'summary' => 'Showing :from–:to of :total :label',
    ],

    'placeholders' => [
        'em_dash' => '—',
        'search' => 'Search…',
    ],
];

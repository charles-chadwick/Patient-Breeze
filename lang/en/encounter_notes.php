<?php

return [
    'tab' => [
        'heading' => 'Encounter Notes',
        'new' => 'New Encounter Note',
        'empty' => 'No encounter notes yet.',
    ],
    'columns' => [
        'title' => 'Title',
        'type' => 'Type',
        'encounter_date' => 'Encounter Date',
        'status' => 'Status',
        'actions' => 'Actions',
    ],
    'form' => [
        'label_type' => 'Type',
        'label_encounter_date' => 'Encounter Date',
        'label_title' => 'Title',
        'label_content' => 'Content',
        'label_appointment' => 'Linked Appointment',
        'placeholder_encounter_date' => 'Select date',
        'placeholder_title' => 'Note title',
        'placeholder_content' => 'Document the encounter…',
        'appointment_none' => 'None',
    ],
    'modal' => [
        'new_title' => 'New Encounter Note',
        'edit_title' => 'Edit Encounter Note',
        'new_description' => 'Document a patient encounter.',
        'edit_description' => 'Update this encounter note.',
        'submit_create' => 'Create Note',
        'submit_update' => 'Save Changes',
    ],
    'actions' => [
        'edit' => 'Edit',
        'delete' => 'Delete',
        'sign' => 'Sign',
        'co_sign' => 'Co-sign',
    ],
    'delete_confirm' => 'Remove this encounter note? This cannot be undone.',
    'signed_by' => 'Signed by :name',
    'co_signed_by' => 'Co-signed by :name',
];

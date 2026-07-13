<?php

/*
|--------------------------------------------------------------------------
| Diagnosis catalog management strings
|--------------------------------------------------------------------------
*/

return [
    'heading' => 'Diagnoses',
    'add' => 'Add Diagnosis',
    'empty' => 'No diagnoses recorded yet.',
    'delete' => 'Remove',
    'delete_confirm' => 'Remove this diagnosis? This cannot be undone.',

    'column_diagnosis' => 'Diagnosis',
    'column_icd10_code' => 'ICD-10 Code',
    'column_diagnosed_on' => 'Diagnosed',
    'column_status' => 'Status',
    'column_recorded' => 'Recorded',
    'column_actions' => 'Actions',

    'search' => [
        'heading' => 'Find a Diagnosis',
        'hint' => 'Search the catalog by name or ICD-10 code, then select one to continue.',
        'placeholder' => 'Search diagnoses…',
        'empty' => 'No diagnoses match your search.',
        'prompt' => 'Start typing to search the diagnosis catalog.',
        'searching' => 'Searching…',
    ],

    'form' => [
        'heading' => 'Add Diagnosis',
        'hint' => 'Review the details below and adjust as needed before saving.',
        'label_diagnosis' => 'Diagnosis',
        'label_icd10_code' => 'ICD-10 Code',
        'label_diagnosed_on' => 'Diagnosis Date',
        'label_status' => 'Status',
        'back' => 'Back to search',
        'submit' => 'Add Diagnosis',
        'cancel' => 'Cancel',
    ],

    'catalog' => [
        'index' => [
            'heading' => 'Diagnosis Catalog',
            'new' => 'New Diagnosis',
            'search_placeholder' => 'Search diagnoses…',
            'column_diagnosis' => 'Diagnosis',
            'column_icd10_code' => 'ICD-10 Code',
            'empty' => 'No diagnoses in the catalog yet.',
            'record_label' => 'diagnoses',
            'delete_confirm' => 'Delete this diagnosis from the catalog? This cannot be undone.',
            'sort' => [
                'diagnosis' => 'Diagnosis',
                'icd10_code' => 'ICD-10 Code',
            ],
        ],
        'form' => [
            'new_title' => 'New Diagnosis',
            'edit_title' => 'Edit :name',
            'hint' => 'Enter the diagnosis catalog details below.',
            'label_diagnosis' => 'Diagnosis',
            'label_icd10_code' => 'ICD-10 Code',
            'submit' => 'Save Diagnosis',
            'submitting' => 'Saving…',
        ],
    ],
];

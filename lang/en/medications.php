<?php

/*
|--------------------------------------------------------------------------
| Patient medication management strings
|--------------------------------------------------------------------------
*/

return [
    'heading' => 'Medications',
    'add' => 'Add Medication',
    'empty' => 'No medications recorded yet.',
    'delete' => 'Remove',
    'delete_confirm' => 'Remove this medication? This cannot be undone.',

    'column_name' => 'Medication',
    'column_type' => 'Type',
    'column_dosage' => 'Dosage',
    'column_dose_form' => 'Form',
    'column_frequency' => 'Frequency',
    'column_amount' => 'Amount',
    'column_ndc' => 'NDC',
    'column_actions' => 'Actions',

    'search' => [
        'heading' => 'Find a Medication',
        'hint' => 'Search the catalog by name, type, or NDC, then select one to continue.',
        'placeholder' => 'Search medications…',
        'empty' => 'No medications match your search.',
        'prompt' => 'Start typing to search the medication catalog.',
        'searching' => 'Searching…',
    ],

    'form' => [
        'heading' => 'Add Medication',
        'hint' => 'Review the details below and adjust as needed before saving.',
        'label_name' => 'Medication Name',
        'label_type' => 'Type',
        'label_dosage' => 'Dosage',
        'label_dose_form' => 'Dose Form',
        'label_frequency' => 'Frequency',
        'label_amount' => 'Amount',
        'label_ndc' => 'NDC',
        'back' => 'Back to search',
        'submit' => 'Add Medication',
        'cancel' => 'Cancel',
    ],

    'catalog' => [
        'index' => [
            'heading' => 'Medication Catalog',
            'new' => 'New Medication',
            'search_placeholder' => 'Search medications…',
            'filter_dose_form' => 'Dose Form',
            'column_name' => 'Name',
            'column_type' => 'Type',
            'column_dosage' => 'Dosage',
            'column_dose_form' => 'Form',
            'column_ndc' => 'NDC',
            'empty' => 'No medications in the catalog yet.',
            'record_label' => 'medications',
            'delete_confirm' => 'Delete this medication from the catalog? This cannot be undone.',
            'sort' => [
                'name' => 'Name',
                'type' => 'Type',
                'dose_form' => 'Form',
                'ndc' => 'NDC',
            ],
        ],
        'form' => [
            'new_title' => 'New Medication',
            'edit_title' => 'Edit :name',
            'label_name' => 'Name',
            'label_type' => 'Type',
            'label_dosage' => 'Dosage',
            'label_dose_form' => 'Dose Form',
            'label_ndc' => 'NDC',
            'submit' => 'Save Medication',
            'submitting' => 'Saving…',
        ],
    ],
];

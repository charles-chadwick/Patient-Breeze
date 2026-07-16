<?php

/*
|--------------------------------------------------------------------------
| Patient vaccine & vaccine catalog strings
|--------------------------------------------------------------------------
*/

return [
    'heading' => 'Vaccines',
    'add' => 'Record Vaccine',
    'empty' => 'No vaccines recorded yet.',
    'delete' => 'Remove',
    'delete_confirm' => 'Remove this vaccine record? This cannot be undone.',
    'expired_lot' => 'Lot expired before this dose was given.',
    'dose_number' => 'Dose :number',
    'unknown_administrator' => 'Not recorded',

    'column_vaccine' => 'Vaccine',
    'column_administered_on' => 'Date',
    'column_dose' => 'Dose',
    'column_lot' => 'Lot',
    'column_administered_by' => 'Administered By',
    'column_status' => 'Status',
    'column_actions' => 'Actions',

    'search' => [
        'heading' => 'Find a Vaccine',
        'hint' => 'Search the catalog by name or CVX code, then select one to continue.',
        'placeholder' => 'Search vaccines…',
        'empty' => 'No vaccines match your search.',
        'prompt' => 'Start typing to search the vaccine catalog.',
        'searching' => 'Searching…',
    ],

    'form' => [
        'heading' => 'Record Vaccine',
        'hint' => 'Review the administration details below and adjust as needed before saving.',
        'section_administration' => 'Administration',
        'section_lot' => 'Lot & Manufacturer',
        'label_vaccine' => 'Vaccine',
        'label_cvx_code' => 'CVX Code',
        'label_administered_on' => 'Date Given',
        'label_dose_number' => 'Dose Number',
        'label_status' => 'Status',
        'label_route' => 'Route',
        'label_site' => 'Site',
        'label_dose_amount' => 'Dose Amount',
        'label_manufacturer' => 'Manufacturer',
        'label_lot_number' => 'Lot Number',
        'label_expires_on' => 'Lot Expires',
        'label_administered_by' => 'Administered By',
        'label_notes' => 'Notes',
        'dose_amount_placeholder' => 'e.g. 0.5 mL',
        'administered_by_hint' => 'Defaults to you when left unset.',
        'none_option' => '—',
        'back' => 'Back to search',
        'submit' => 'Record Vaccine',
        'cancel' => 'Cancel',
    ],

    'catalog' => [
        'index' => [
            'heading' => 'Vaccine Catalog',
            'new' => 'New Vaccine',
            'search_placeholder' => 'Search vaccines…',
            'column_name' => 'Vaccine',
            'column_cvx_code' => 'CVX Code',
            'empty' => 'No vaccines in the catalog yet.',
            'record_label' => 'vaccines',
            'delete_confirm' => 'Delete this vaccine from the catalog? This cannot be undone.',
            'sort' => [
                'name' => 'Vaccine',
                'cvx_code' => 'CVX Code',
            ],
        ],
        'form' => [
            'new_title' => 'New Vaccine',
            'edit_title' => 'Edit :name',
            'hint' => 'Enter the vaccine catalog details below.',
            'label_name' => 'Vaccine',
            'label_cvx_code' => 'CVX Code',
            'cvx_code_hint' => 'The CDC code identifying this vaccine product.',
            'submit' => 'Save Vaccine',
            'submitting' => 'Saving…',
        ],
    ],
];

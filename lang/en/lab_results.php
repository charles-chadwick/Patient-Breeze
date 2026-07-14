<?php

/*
|--------------------------------------------------------------------------
| Patient lab results strings
|--------------------------------------------------------------------------
*/

return [
    'heading' => 'Lab Results',
    'add' => 'Add Result',
    'empty' => 'No lab results recorded yet.',
    'delete' => 'Remove',
    'delete_confirm' => 'Remove this lab result? This cannot be undone.',
    'no_range' => 'No reference range',

    'column_name' => 'Test',
    'column_value' => 'Result',
    'column_reference' => 'Reference Range',
    'column_flag' => 'Flag',
    'column_collected' => 'Collected',
    'column_performing_lab' => 'Performing Lab',
    'column_actions' => 'Actions',

    'search' => [
        'heading' => 'Find a Lab Test',
        'hint' => 'Search the catalog by test name, performing lab, or CPT code, then select one to record a result.',
        'placeholder' => 'Search lab tests…',
        'empty' => 'No lab tests match your search.',
        'prompt' => 'Start typing to search the lab order catalog.',
        'searching' => 'Searching…',
    ],

    'form' => [
        'heading' => 'Record Result',
        'hint' => 'Enter the measured value. The reference range is based on this patient’s sex and age.',
        'label_value' => 'Result Value',
        'label_unit' => 'Unit',
        'label_collected_at' => 'Collected On',
        'label_notes' => 'Notes',
        'reference_heading' => 'Reference range for this patient',
        'reference_for' => ':gender · :age yrs',
        'reference_none' => 'No reference range is defined for this test and patient.',
        'preview_flag' => 'This value would be flagged:',
        'back' => 'Back to search',
        'submit' => 'Save Result',
        'cancel' => 'Cancel',
    ],
];

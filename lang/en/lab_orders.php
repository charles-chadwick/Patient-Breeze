<?php

/*
|--------------------------------------------------------------------------
| Lab order catalog management strings
|--------------------------------------------------------------------------
*/

return [
    'heading' => 'Lab Orders',
    'add' => 'Add Lab Order',
    'empty' => 'No lab orders recorded yet.',
    'delete' => 'Remove',
    'delete_confirm' => 'Remove this lab order? This cannot be undone.',

    'search' => [
        'heading' => 'Find a Lab Order',
        'hint' => 'Search the catalog by order name, performing lab, or CPT code, then select one to continue.',
        'placeholder' => 'Search lab orders…',
        'empty' => 'No lab orders match your search.',
        'prompt' => 'Start typing to search the lab order catalog.',
        'searching' => 'Searching…',
    ],

    'catalog' => [
        'index' => [
            'heading' => 'Lab Order Catalog',
            'new' => 'New Lab Order',
            'search_placeholder' => 'Search lab orders…',
            'column_name' => 'Order Name',
            'column_performing_lab' => 'Performing Lab',
            'column_cpt_code' => 'CPT Code',
            'empty' => 'No lab orders in the catalog yet.',
            'record_label' => 'lab orders',
            'ranges_action' => 'Ranges',
            'delete_confirm' => 'Delete this lab order from the catalog? This cannot be undone.',
            'sort' => [
                'name' => 'Order Name',
                'performing_lab' => 'Performing Lab',
                'cpt_code' => 'CPT Code',
            ],
        ],
        'form' => [
            'new_title' => 'New Lab Order',
            'edit_title' => 'Edit :name',
            'hint' => 'Enter the lab order catalog details below.',
            'label_name' => 'Order Name',
            'label_performing_lab' => 'Performing Lab',
            'label_cpt_code' => 'CPT Code',
            'submit' => 'Save Lab Order',
            'submitting' => 'Saving…',
        ],
        'ranges' => [
            'heading' => 'Reference Ranges',
            'hint' => 'Normal ranges for this test, optionally scoped by sex and age band. The most specific match applies to a patient’s result.',
            'add' => 'Add Range',
            'empty' => 'No reference ranges defined yet.',
            'save_first' => 'Save the lab order first, then add reference ranges.',
            'column_sex' => 'Sex',
            'column_age' => 'Age',
            'column_low' => 'Low',
            'column_high' => 'High',
            'column_unit' => 'Unit',
            'column_actions' => 'Actions',
            'any' => 'Any',
            'age_from' => ':min+',
            'age_to' => '≤ :max',
            'age_between' => ':min–:max',
            'delete_confirm' => 'Remove this reference range? This cannot be undone.',
            'modal' => [
                'new_title' => 'Add Reference Range',
                'edit_title' => 'Edit Reference Range',
                'hint' => 'Leave a bound empty for an open-ended range (e.g. only a high value for “< 200”).',
                'label_sex' => 'Sex',
                'label_min_age' => 'Min Age',
                'label_max_age' => 'Max Age',
                'label_low' => 'Low Value',
                'label_high' => 'High Value',
                'label_unit' => 'Unit',
                'sex_any' => 'Any sex',
                'submit' => 'Save Range',
            ],
        ],
    ],
];

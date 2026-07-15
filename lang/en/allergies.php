<?php

/*
|--------------------------------------------------------------------------
| Patient allergy & allergen catalog strings
|--------------------------------------------------------------------------
*/

return [
    'heading' => 'Allergies',
    'add' => 'Add Allergy',
    'empty' => 'No allergies recorded yet.',
    'delete' => 'Remove',
    'delete_confirm' => 'Remove this allergy? This cannot be undone.',

    'column_allergen' => 'Allergen',
    'column_category' => 'Category',
    'column_reactions' => 'Reactions',
    'column_severity' => 'Severity',
    'column_status' => 'Status',
    'column_onset_on' => 'Onset',
    'column_notes' => 'Notes',
    'column_actions' => 'Actions',

    'banner' => [
        'label' => 'Allergies',
        'not_reviewed' => 'Allergy list not yet reviewed',
        'no_known_allergies' => 'No known allergies',
        'confirmed_by' => 'Confirmed by :name on :date',
        'confirmed_on' => 'Confirmed on :date',
        'reviewed_by' => 'Reviewed by :name on :date',
        'reviewed_on' => 'Reviewed on :date',
        'mark_no_known' => 'Mark No Known Allergies',
        'mark_reviewed' => 'Mark Reviewed',
        'confirm_no_known_title' => 'Mark No Known Allergies',
        'confirm_no_known_description' => 'Record that this patient has been asked and reports no known allergies. This is stamped with your name and today’s date.',
        'confirm_reviewed_description' => 'Record that you have reviewed this patient’s allergy list as current. This is stamped with your name and today’s date.',
    ],

    'search' => [
        'heading' => 'Find an Allergen',
        'hint' => 'Search the catalog by name or category, then select one to continue.',
        'placeholder' => 'Search allergens…',
        'empty' => 'No allergens match your search.',
        'prompt' => 'Start typing to search the allergen catalog.',
        'searching' => 'Searching…',
    ],

    'form' => [
        'heading' => 'Add Allergy',
        'hint' => 'Review the details below and adjust as needed before saving.',
        'label_allergen' => 'Allergen',
        'label_category' => 'Category',
        'label_reactions' => 'Reaction(s)',
        'label_severity' => 'Severity',
        'label_status' => 'Status',
        'label_onset_on' => 'Onset Date',
        'label_notes' => 'Notes',
        'reactions_hint' => 'Select every reaction the patient has had.',
        'back' => 'Back to search',
        'submit' => 'Add Allergy',
        'cancel' => 'Cancel',
    ],

    'catalog' => [
        'index' => [
            'heading' => 'Allergen Catalog',
            'new' => 'New Allergen',
            'search_placeholder' => 'Search allergens…',
            'column_name' => 'Allergen',
            'column_category' => 'Category',
            'empty' => 'No allergens in the catalog yet.',
            'record_label' => 'allergens',
            'delete_confirm' => 'Delete this allergen from the catalog? This cannot be undone.',
            'sort' => [
                'name' => 'Allergen',
                'category' => 'Category',
            ],
        ],
        'form' => [
            'new_title' => 'New Allergen',
            'edit_title' => 'Edit :name',
            'hint' => 'Enter the allergen catalog details below.',
            'label_name' => 'Allergen',
            'label_category' => 'Category',
            'submit' => 'Save Allergen',
            'submitting' => 'Saving…',
        ],
    ],
];

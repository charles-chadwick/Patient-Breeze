<?php

/*
|--------------------------------------------------------------------------
| Insurance company catalog strings
|--------------------------------------------------------------------------
*/

return [
    'index' => [
        'heading' => 'Insurance Companies',
        'new' => 'New Company',
        'search_placeholder' => 'Search companies…',
        'column_name' => 'Company',
        'column_payer_id' => 'Payer ID',
        'column_location' => 'Location',
        'column_phone' => 'Phone',
        'empty' => 'No insurance companies in the catalog yet.',
        'record_label' => 'companies',
        'delete_confirm' => 'Delete this insurance company from the catalog? This cannot be undone.',
        'sort' => [
            'name' => 'Company',
            'payer_id' => 'Payer ID',
            'city' => 'City',
        ],
    ],

    'form' => [
        'new_title' => 'New Insurance Company',
        'edit_title' => 'Edit :name',
        'hint' => 'Enter the insurance company details below.',
        'section_address' => 'Mailing Address',
        'section_contact' => 'Contact',
        'label_name' => 'Company Name',
        'label_payer_id' => 'Payer ID',
        'label_address_line1' => 'Address Line 1',
        'label_address_line2' => 'Address Line 2',
        'label_city' => 'City',
        'label_state' => 'State',
        'label_postal_code' => 'Postal Code',
        'label_phone' => 'Phone',
        'label_fax' => 'Fax',
        'label_website' => 'Website',
        'label_notes' => 'Notes',
        'payer_id_hint' => 'Electronic payer identifier, if known.',
        'submit' => 'Save Company',
        'submitting' => 'Saving…',
    ],
];

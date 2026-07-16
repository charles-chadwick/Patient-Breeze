<?php

return [
    // Global audit log page
    'index' => [
        'heading' => 'Audit Log',
        'subheading' => 'Every change recorded across the system.',
        'column_causer' => 'User',
        'column_action' => 'Action',
        'column_subject' => 'Record',
        'column_when' => 'When',
        'empty' => 'No activity matches these filters.',
        'record_label' => 'entries',
        'filter_causer' => 'User',
        'filter_subject' => 'Record type',
        'filter_event' => 'Action',
        'filter_date_from' => 'From',
        'filter_date_to' => 'To',
        'filter_all' => 'All',
        'reset_filters' => 'Reset',
        'scoped_to_patient' => 'Showing activity for :name',
        'view_all' => 'View all activity',
        'export' => 'Export PDF',
    ],

    // PDF export
    'export' => [
        'title' => 'Audit Log',
        'generated_at' => 'Generated :datetime',
        'total' => ':count entries',
        'filters_label' => 'Filters',
        'scope_patient' => 'Patient: :name',
        'filter_user' => 'User: :name',
        'filter_record' => 'Record type: :type',
        'filter_action' => 'Action: :action',
        'filter_from' => 'From :date',
        'filter_to' => 'To :date',
        'truncated' => 'Only the most recent :count entries are shown. Narrow the filters to export fewer results.',
        'system_user' => 'System',
        'empty' => 'No activity matches these filters.',
        'col_when' => 'When',
        'col_user' => 'User',
        'col_action' => 'Action',
        'col_record' => 'Record',
        'col_changes' => 'Changes',
    ],

    // Patient chart History tab
    'tab' => [
        'heading' => 'History',
        'empty' => 'No recorded history for this patient yet.',
    ],

    'system_user' => 'System',
    'changes_heading' => 'Changes',
    'no_changes' => 'No field changes recorded.',
    'old_value' => 'Before',
    'new_value' => 'After',
    'empty_value' => '—',

    // Event verbs (activity_log.event)
    'actions' => [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
    ],

    // Subject-type labels, keyed by class basename.
    'subjects' => [
        'Patient' => 'Patient',
        'User' => 'Staff User',
        'Appointment' => 'Appointment',
        'EncounterNote' => 'Encounter Note',
        'PatientMedication' => 'Medication',
        'PatientDiagnosis' => 'Diagnosis',
        'PatientAllergy' => 'Allergen',
        'PatientVaccine' => 'Vaccine',
        'Medication' => 'Medication (Catalog)',
        'Diagnosis' => 'Diagnosis (Catalog)',
        'Allergen' => 'Allergen (Catalog)',
        'Vaccine' => 'Vaccine (Catalog)',
        'Discussion' => 'Discussion',
        'DiscussionPost' => 'Discussion Post',
        'DiscussionParticipant' => 'Discussion Participant',
        'Note' => 'Note',
        'Document' => 'Document',
        'Contact' => 'Contact',
        'Media' => 'File',
    ],
];

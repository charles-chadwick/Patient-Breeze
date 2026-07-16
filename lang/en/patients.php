<?php

/*
|--------------------------------------------------------------------------
| Patients domain strings
|--------------------------------------------------------------------------
*/

return [
    'index' => [
        'heading' => 'All Patients',
        'new_patient' => '+ New Patient',
        'search_placeholder' => 'Search by name, MRN, DOB, or email…',
        'column_name' => 'Name',
        'column_mrn' => 'MRN',
        'column_date_of_birth' => 'Date of Birth',
        'column_gender' => 'Gender',
        'column_blood_type' => 'Blood Type',
        'column_email' => 'Email',
        'empty' => 'No patients found.',
        'record_label' => 'patients',
    ],

    'sort' => [
        'last_name' => 'Last Name',
        'first_name' => 'First Name',
        'date_of_birth' => 'Date of Birth',
        'blood_type' => 'Blood Type',
    ],

    'show' => [
        'edit_patient' => 'Edit Patient',
        'view_audit_log' => 'Audit Log',
        'delete_patient' => 'Delete Patient',
        'delete_confirm' => 'Delete this patient? Their record will be archived and can be restored by an administrator.',
        'tab_demographics' => 'Demographics',
        'tab_contacts' => 'Contacts',
        'tab_notes' => 'Notes',
        'tab_encounters' => 'Encounters',
        'tab_discussions' => 'Discussions',
        'tab_history' => 'History',
        'tab_appointments' => 'Appointments',
        'tab_vitals' => 'Vitals',
        'tab_medications' => 'Medications',
        'tab_diagnoses' => 'Diagnoses',
        'tab_allergies' => 'Allergies',
        'tab_vaccines' => 'Vaccines',
        'tab_lab_results' => 'Lab Results',
        'tab_documents' => 'Documents',
        'appointments_heading' => 'Appointments',
        'appointments_search_placeholder' => 'Search reason or notes…',
        'new_appointment' => '+ New Appointment',
        'appointments_empty_search' => 'No appointments match your search.',
        'appointments_empty' => 'No appointments on record.',
        'column_date' => 'Date',
        'column_time' => 'Time',
        'column_reason' => 'Reason',
        'column_staff' => 'Staff',
        'column_status' => 'Status',
        'column_notes' => 'Notes',
        'appointments_record_label' => 'appointments',
    ],

    'form' => [
        'new_title' => 'New Patient',
        'edit_title' => 'Edit :name',
        'section_avatar' => 'Avatar',
        'section_identity' => 'Identity',
        'section_medical' => 'Medical',
        'label_prefix' => 'Prefix',
        'label_first_name' => 'First Name',
        'label_middle_name' => 'Middle Name',
        'label_last_name' => 'Last Name',
        'label_suffix' => 'Suffix',
        'label_email' => 'Email',
        'label_date_of_birth' => 'Date of Birth',
        'label_gender_at_birth' => 'Gender at Birth',
        'label_gender_identity' => 'Gender Identity',
        'label_blood_type' => 'Blood Type',
        'placeholder_prefix' => 'Dr., Mr., Ms.…',
        'placeholder_first_name' => 'First name',
        'placeholder_middle_name' => 'Middle name',
        'placeholder_last_name' => 'Last name',
        'placeholder_suffix' => 'MD, DO, Jr.…',
        'placeholder_email' => 'email@example.com',
        'placeholder_date_of_birth' => 'Select date of birth',
        'submit' => 'Save Patient',
        'submitting' => 'Saving…',
    ],

    'card' => [
        'full_name' => 'Full Name',
        'date_of_birth' => 'Date of Birth',
        'gender_at_birth' => 'Gender at Birth',
        'gender_identity' => 'Gender Identity',
        'blood_type' => 'Blood Type',
        'email' => 'Email',
    ],
];

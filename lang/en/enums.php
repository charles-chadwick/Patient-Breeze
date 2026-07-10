<?php

/*
|--------------------------------------------------------------------------
| Enum display labels
|--------------------------------------------------------------------------
|
| Enum *values* are stored data and stay unchanged; only their human-facing
| labels are translated. Keyed by the backing value so both PHP
| (__('enums.user_role.Super Admin')) and Vue ($t('enums.user_role.' + name))
| can resolve a label from a value.
|
*/

return [
    'user_role' => [
        'Super Admin' => 'Super Admin',
        'Doctor' => 'Doctor',
        'Nurse' => 'Nurse',
        'Medical Assistant' => 'Medical Assistant',
        'Staff' => 'Staff',
    ],

    'appointment_role' => [
        'Primary' => 'Primary',
        'Assistant' => 'Assistant',
    ],

    'setting_key' => [
        'Theme' => 'Theme',
        'Email Notifications' => 'Email Notifications',
        'Items Per Page' => 'Items Per Page',
        'Receive Portal Messages' => 'Receive Portal Messages',
    ],

    'toggle_value' => [
        'Enabled' => 'Enabled',
        'Disabled' => 'Disabled',
    ],

    'appointment_status' => [
        'Scheduled' => 'Scheduled',
        'Confirmed' => 'Confirmed',
        'Completed' => 'Completed',
        'Cancelled' => 'Cancelled',
        'Rescheduled' => 'Rescheduled',
        'NoShow' => 'No Show',
    ],

    'blood_type' => [
        'A+' => 'A+',
        'A-' => 'A-',
        'B+' => 'B+',
        'B-' => 'B-',
        'AB+' => 'AB+',
        'AB-' => 'AB-',
        'O+' => 'O+',
        'O-' => 'O-',
    ],

    'contact_type' => [
        'Personal' => 'Personal',
        'Work' => 'Work',
        'Emergency' => 'Emergency',
        'Guardian' => 'Guardian',
        'Spouse' => 'Spouse',
        'Other' => 'Other',
    ],

    'note_type' => [
        'General' => 'General',
        'Clinical' => 'Clinical',
        'Administrative' => 'Administrative',
        'CarePlan' => 'Care Plan',
    ],

    'encounter_note_type' => [
        'Progress' => 'Progress Note',
        'InitialVisit' => 'Initial Visit',
        'FollowUp' => 'Follow-up',
        'Consultation' => 'Consultation',
        'Procedure' => 'Procedure',
        'DischargeSummary' => 'Discharge Summary',
        'Telephone' => 'Telephone',
    ],

    'encounter_note_status' => [
        'Unsigned' => 'Unsigned',
        'Signed' => 'Signed',
        'CoSigned' => 'Co-signed',
    ],

    'discussion_post_status' => [
        'Draft' => 'Draft',
        'Published' => 'Published',
        'Read Only' => 'Read Only',
    ],

    'discussion_type' => [
        'General' => 'General',
        'Internal' => 'Internal',
        'Portal Message' => 'Portal Message',
    ],

    'document_type' => [
        'LabResult' => 'Lab Result',
        'Insurance' => 'Insurance',
        'Referral' => 'Referral',
        'Consent' => 'Consent',
        'Prescription' => 'Prescription',
        'Identification' => 'Identification',
        'Certification' => 'Certification',
        'Note' => 'Note',
        'Other' => 'Other',
    ],

    'dose_form' => [
        'Tablet' => 'Tablet',
        'Capsule' => 'Capsule',
        'Solution' => 'Solution',
        'Suspension' => 'Suspension',
        'Syrup' => 'Syrup',
        'Elixir' => 'Elixir',
        'Injection' => 'Injection',
        'Cream' => 'Cream',
        'Ointment' => 'Ointment',
        'Gel' => 'Gel',
        'Lotion' => 'Lotion',
        'Suppository' => 'Suppository',
        'Patch' => 'Patch',
        'Inhaler' => 'Inhaler',
        'Drops' => 'Drops',
        'Spray' => 'Spray',
        'Powder' => 'Powder',
        'Lozenge' => 'Lozenge',
        'Foam' => 'Foam',
        'Granules' => 'Granules',
    ],

    'frequency' => [
        'Once Daily' => 'Once Daily',
        'Twice Daily' => 'Twice Daily',
        'Three Times Daily' => 'Three Times Daily',
        'Four Times Daily' => 'Four Times Daily',
        'Every Morning' => 'Every Morning',
        'Every Night' => 'Every Night',
        'Every Other Day' => 'Every Other Day',
        'Weekly' => 'Weekly',
        'Monthly' => 'Monthly',
        'As Needed' => 'As Needed',
    ],

    'appointment_request_status' => [
        'Pending' => 'Pending',
        'Approved' => 'Approved',
        'Declined' => 'Declined',
    ],

    'gender_at_birth' => [
        'Male' => 'Male',
        'Female' => 'Female',
        'Unknown' => 'Unknown',
    ],

    'gender_identity' => [
        'Male' => 'Male',
        'Female' => 'Female',
        'Non-binary' => 'Non-binary',
        'Prefer not to say' => 'Prefer not to say',
    ],
];

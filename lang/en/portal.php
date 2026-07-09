<?php

/*
|--------------------------------------------------------------------------
| Patient portal domain strings
|--------------------------------------------------------------------------
*/

return [
    'login' => [
        'title' => 'Sign In – Patient Portal',
        'heading' => 'Sign In',
        'subtitle' => 'Access your health records and appointments.',
        'label_email' => 'Email',
        'label_password' => 'Password',
        'placeholder_email' => 'you@example.com',
        'placeholder_password' => '••••••••',
        'submit' => 'Sign In to Your Portal',
        'submitting' => 'Signing in…',
    ],

    'dashboard' => [
        'welcome_back' => 'Welcome back',
        'mrn' => 'MRN',
        'date_of_birth' => 'Date of Birth',
        'blood_type' => 'Blood Type',
        'gender_identity' => 'Gender Identity',
        'appointments_heading' => 'Upcoming Appointments',
        'appointments_empty' => 'No upcoming appointments.',
        'request_appointment' => 'Request Appointment',
        'requests_heading' => 'Appointment Requests',
        'requests_empty' => 'No appointment requests.',
        'messages_heading' => 'Messages',
        'messages_empty' => 'No messages.',
        'documents_heading' => 'Documents',
        'documents_empty' => 'No documents on file.',
        'documents_upload' => 'Upload',
    ],

    'appointments' => [
        'modal_title' => 'Request an Appointment',
        'modal_subtitle' => 'Choose a provider and a preferred time. Our team will confirm your request.',
        'label_provider' => 'Provider',
        'placeholder_provider' => 'Search for a provider…',
        'label_date' => 'Preferred date',
        'label_start_time' => 'Start time',
        'label_end_time' => 'End time',
        'label_reason' => 'Reason for visit',
        'placeholder_reason' => 'e.g. Annual physical exam',
        'label_notes' => 'Additional notes',
        'placeholder_notes' => 'Anything else we should know? (optional)',
        'submit' => 'Submit Request',
        'submitting' => 'Submitting…',
        'cancel' => 'Cancel',
        'conflict' => ':name is already booked during that time. Please choose another slot.',
        'invalid_provider' => 'That provider is not available for appointment requests.',
        'provider_required' => 'Please choose a provider.',
    ],

    'messages' => [
        'title' => 'Messages',
        'heading' => 'Messages',
        'new_message' => 'New Message',
        'label_subject' => 'Subject',
        'placeholder_subject' => 'What\'s this about?',
        'label_recipients' => 'Send to',
        'placeholder_recipients' => 'Search for a staff member...',
        'recipients_hint' => 'Optionally direct this message to specific staff members.',
        'recipient_unavailable' => 'That staff member is not available to receive messages.',
        'label_message' => 'Message',
        'send' => 'Send',
        'empty' => 'No messages yet.',
        'no_subject' => '(No subject)',
        'reply' => 'Reply',
        'placeholder_reply' => 'Write a reply...',
        'send_reply' => 'Send Reply',
    ],
];

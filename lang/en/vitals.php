<?php

/*
|--------------------------------------------------------------------------
| Patient vitals & flowsheet strings
|--------------------------------------------------------------------------
*/

return [
    'heading' => 'Vitals',
    'add' => 'Record Vitals',
    'empty' => 'No vitals recorded yet.',
    'delete' => 'Remove',
    'delete_confirm' => 'Remove this vitals set? This cannot be undone.',
    'unknown_recorder' => 'Not recorded',
    'abnormal' => 'Outside the normal range',

    'latest' => [
        'heading' => 'Latest Readings',
        'measured' => 'Measured :date',
        'bmi' => 'BMI',
        'no_reading' => '—',
    ],

    'flowsheet' => [
        'heading' => 'Flowsheet',
        'measurement' => 'Measurement',
        'recorded_by' => 'Recorded by',
    ],

    'form' => [
        'heading' => 'Record Vitals',
        'hint' => 'Enter the readings taken. Leave anything not measured blank — at least one is required.',
        'section_measurements' => 'Measurements',
        'section_blood_pressure' => 'Blood Pressure',
        'section_context' => 'Context',
        'label_measured_at' => 'Date & Time',
        'label_systolic' => 'Systolic',
        'label_diastolic' => 'Diastolic',
        'label_position' => 'Position',
        'label_heart_rate' => 'Heart Rate',
        'label_respiratory_rate' => 'Respiratory Rate',
        'label_temperature' => 'Temperature',
        'label_temperature_site' => 'Temp. Site',
        'label_oxygen_saturation' => 'O₂ Saturation',
        'label_oxygen_delivery' => 'O₂ Delivery',
        'label_weight' => 'Weight',
        'label_height' => 'Height',
        'label_bmi' => 'BMI',
        'label_pain_score' => 'Pain (0–10)',
        'label_recorded_by' => 'Recorded By',
        'label_notes' => 'Notes',
        'bmi_hint' => 'Calculated from weight and height.',
        'recorded_by_hint' => 'Defaults to you when left unset.',
        'none_option' => '—',
        'submit' => 'Record Vitals',
        'cancel' => 'Cancel',
    ],

    'units' => [
        'mmhg' => 'mmHg',
        'bpm' => 'bpm',
        'breaths' => '/min',
        'celsius' => '°C',
        'percent' => '%',
        'kg' => 'kg',
        'cm' => 'cm',
    ],
];

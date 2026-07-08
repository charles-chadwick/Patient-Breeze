<?php

/*
|--------------------------------------------------------------------------
| Two-factor authentication strings
|--------------------------------------------------------------------------
*/

return [
    // Settings management
    'settings_title' => 'Two-Factor Authentication',
    'settings_heading' => 'Two-Factor Authentication',
    'settings_description' => 'Add an extra layer of security to your account by requiring a one-time code from your authenticator app when you sign in.',
    'status_enabled' => 'Two-factor authentication is enabled.',
    'status_disabled' => 'Two-factor authentication is not enabled.',
    'enable' => 'Enable',
    'disable' => 'Disable',
    'setup_heading' => 'Finish setting up',
    'setup_instructions' => 'Scan the QR code below with an authenticator app such as Google Authenticator or Authy, then enter the generated code to confirm.',
    'confirm_label' => 'Authentication code',
    'confirm_placeholder' => '123456',
    'confirm' => 'Confirm',
    'recovery_codes_heading' => 'Recovery codes',
    'recovery_codes_description' => 'Store these recovery codes in a safe place. Each can be used once to access your account if you lose your authenticator device.',
    'regenerate_recovery_codes' => 'Regenerate recovery codes',

    // Password confirmation
    'confirm_password_title' => 'Confirm Password',
    'confirm_password_heading' => 'Confirm Password',
    'confirm_password_instructions' => 'For your security, please confirm your password to continue.',
    'confirm_password_label' => 'Password',
    'confirm_password_placeholder' => '••••••••',
    'confirm_password_submit' => 'Confirm',
    'reveal_recovery_codes' => 'Confirm your password to view recovery codes',

    // Login challenge
    'challenge_title' => 'Two-Factor Confirmation',
    'challenge_heading' => 'Two-Factor Confirmation',
    'challenge_code_instructions' => 'Enter the authentication code provided by your authenticator app.',
    'challenge_recovery_instructions' => 'Enter one of your emergency recovery codes.',
    'code_label' => 'Code',
    'code_placeholder' => '123456',
    'recovery_code_label' => 'Recovery code',
    'recovery_code_placeholder' => 'xxxxxxxxxx-xxxxxxxxxx',
    'use_recovery_code' => 'Use a recovery code',
    'use_authentication_code' => 'Use an authentication code',
    'submit' => 'Verify',
    'submitting' => 'Verifying…',

    // Flash / validation
    'enabled' => 'Two-factor authentication has been enabled.',
    'disabled' => 'Two-factor authentication has been disabled.',
    'invalid_code' => 'The provided authentication code was invalid.',
    'invalid_recovery_code' => 'The provided recovery code was invalid.',
    'code_required' => 'Please provide an authentication or recovery code.',
];

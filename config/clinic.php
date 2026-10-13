<?php

return [
    'alert_email' => env('CLINIC_ALERT_EMAIL'),
    'alerts_enabled' => filter_var(env('CLINIC_ALERTS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'smtp_test_recipient' => env('MAIL_TEST_RECIPIENT'),
    'alert_greeting' => env('CLINIC_ALERT_GREETING', 'Ms. Torre'),
    'timezone' => env('CLINIC_TIMEZONE', 'Asia/Manila'),
    'near_expiration_days' => (int) env('CLINIC_NEAR_EXPIRATION_DAYS', 30),
];

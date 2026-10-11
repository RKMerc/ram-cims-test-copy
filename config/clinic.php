<?php

return [
    'alert_email' => env('CLINIC_ALERT_EMAIL'),
    'alert_greeting' => env('CLINIC_ALERT_GREETING', 'Ms. Torre'),
    'timezone' => env('CLINIC_TIMEZONE', 'Asia/Manila'),
    'near_expiration_days' => (int) env('CLINIC_NEAR_EXPIRATION_DAYS', 30),
];

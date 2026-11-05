<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Activity emails
    |--------------------------------------------------------------------------
    |
    | Emails about activity in the system: payments, published or postponed
    | sessions, registrations, interviews and recruitment requests. Off by
    | default while accounts use dummy addresses, so bounced mail does not get
    | the SMTP account flagged as spam. Login codes (OTP) are always sent.
    |
    */

    'activity_emails' => (bool) env('ACTIVITY_EMAILS', false),

    /*
    |--------------------------------------------------------------------------
    | Registration fee
    |--------------------------------------------------------------------------
    |
    | Amount (in Rupiah) job seekers pay via QRIS before they appear in the
    | candidate list. A verified payment keeps the account active for
    | `membership_months` months, after which it has to be renewed.
    |
    */

    'registration_fee' => (int) env('REGISTRATION_FEE', 50000),

    'membership_months' => (int) env('MEMBERSHIP_MONTHS', 12),

    /*
    |--------------------------------------------------------------------------
    | Test session fees
    |--------------------------------------------------------------------------
    |
    | `exam_session_fee` is paid by the LPK to publish a test session.
    | `exam_registration_fee` is paid by a job seeker to join one session.
    |
    */

    'exam_session_fee' => (int) env('EXAM_SESSION_FEE', 50000),

    'exam_registration_fee' => (int) env('EXAM_REGISTRATION_FEE', 6500),

    /*
    |--------------------------------------------------------------------------
    | Recruitment fees
    |--------------------------------------------------------------------------
    |
    | `interview_fee` is paid by the company once per test session to invite
    | candidates from its ranking to an interview (up to the quota).
    | `medical_checkup_fee` is charged per candidate for the final medical
    | checkup stage.
    |
    */

    'interview_fee' => (int) env('INTERVIEW_FEE', 500000),

    'medical_checkup_fee' => (int) env('MEDICAL_CHECKUP_FEE', 600000),

    /*
    |--------------------------------------------------------------------------
    | Payment demo
    |--------------------------------------------------------------------------
    |
    | Secret part of the demo page URL (/demo-pembayaran/{key}) where payment
    | gateway reviewers can pay every catalog item with a real QRIS charge
    | without going through the recruitment flow. Demo payments never change
    | anything else. Leave empty to switch the page off.
    |
    */

    'payment_demo_key' => env('PAYMENT_DEMO_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Two-step verification
    |--------------------------------------------------------------------------
    |
    | Global switch for the email OTP step at login. When false nobody gets an
    | OTP (handy during development). When true, the per-user setting managed
    | by the super admin decides.
    |
    */

    'two_factor_enabled' => (bool) env('TWO_FACTOR_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | QRIS image
    |--------------------------------------------------------------------------
    |
    | Path (relative to the public directory) of the static QRIS code shown on
    | the payment page. Replace the file with your own merchant QRIS.
    |
    */

    'qris_image' => env('QRIS_IMAGE', 'images/qris.png'),

    'qris_merchant_name' => env('QRIS_MERCHANT_NAME', 'Careja'),

    /*
    |--------------------------------------------------------------------------
    | Upload limit
    |--------------------------------------------------------------------------
    |
    | Maximum size in kilobytes for candidate documents and payment proofs.
    |
    */

    'max_upload_kb' => (int) env('MAX_UPLOAD_KB', 2048),

];

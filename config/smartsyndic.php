<?php

/**
 * Smart Syndic — Custom Platform Configuration
 *
 * All business-specific constants, limits, and feature flags.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Invitation Code Settings
    |--------------------------------------------------------------------------
    */
    'invitation_code_length' => 8,

    /*
    |--------------------------------------------------------------------------
    | Upload Constraints (Security Directive §1.1)
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'max_size_kb'    => 5120, // 5MB
        'allowed_mimes'  => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
        'storage_disk'   => env('FILESYSTEM_DISK', 's3'),
        'signed_url_ttl' => 15, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting (Security Directive §1.2)
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'auth'         => (int) env('RATE_LIMIT_AUTH', 5),        // per minute
        'invite_code'  => (int) env('RATE_LIMIT_INVITE_CODE', 10),
        'api'          => (int) env('RATE_LIMIT_API', 60),
        'financial'    => (int) env('RATE_LIMIT_FINANCIAL', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Moroccan Phone Validation Regex
    |--------------------------------------------------------------------------
    */
    'phone_regex' => '/^(?:(?:\+|00)212|0)[5-7]\d{8}$/',

    /*
    |--------------------------------------------------------------------------
    | Moroccan RIB Validation (24 digits)
    |--------------------------------------------------------------------------
    */
    'rib_length' => 24,

    /*
    |--------------------------------------------------------------------------
    | Supported Locales & Default
    |--------------------------------------------------------------------------
    */
    'locales' => ['ar', 'fr', 'en'],
    'default_locale' => 'ar',

    /*
    |--------------------------------------------------------------------------
    | Payment Receipt Configuration
    |--------------------------------------------------------------------------
    */
    'receipt' => [
        'prefix'   => 'REC',
        'format'   => 'A5',
        'orientation' => 'landscape',
    ],

    /*
    |--------------------------------------------------------------------------
    | Assembly Report Configuration
    |--------------------------------------------------------------------------
    */
    'assembly_report' => [
        'format'      => 'A4',
        'orientation' => 'portrait',
    ],

    /*
    |--------------------------------------------------------------------------
    | Expense Categories
    |--------------------------------------------------------------------------
    */
    'expense_categories' => [
        'ELEVATOR',
        'CLEANING',
        'SECURITY',
        'WATER_ELECTRICITY',
        'GARDENING',
        'MAINTENANCE',
        'OTHER',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    */
    'payment_methods' => ['CASH', 'BANK_TRANSFER', 'CHEQUE'],

    /*
    |--------------------------------------------------------------------------
    | Plan Types & Tenant Statuses
    |--------------------------------------------------------------------------
    */
    'plan_types'      => ['TRIAL', 'PRO', 'ENTERPRISE'],
    'tenant_statuses' => ['PENDING_APPROVAL', 'ACTIVE', 'SUSPENDED', 'EXPIRED'],

    /*
    |--------------------------------------------------------------------------
    | User Roles & Statuses
    |--------------------------------------------------------------------------
    */
    'roles'          => ['SUPER_ADMIN', 'ADMIN_SYNDIC', 'RESIDENT'],
    'user_statuses'  => ['PENDING', 'ACTIVE', 'BLOCKED'],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Cloud API
    |--------------------------------------------------------------------------
    */
    'whatsapp' => [
        'api_url'         => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token'    => env('WHATSAPP_ACCESS_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging
    |--------------------------------------------------------------------------
    */
    'fcm' => [
        'credentials_path' => env('FCM_CREDENTIALS_PATH', 'storage/app/firebase-credentials.json'),
        'project_id'       => env('FCM_PROJECT_ID'),
    ],
];

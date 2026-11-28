<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'customer_app' => [
        'url' => env('CUSTOMER_APP_URL'),
    ],

    'wwd' => [
        'claim_url' => env('WWD_CLAIM_URL', 'https://biz.worldweb-directory.com/claim-business'),
        // Business owners' login page (WWD Business), used as {{LoginURL}} in emails
        'business_login_url' => env('WWD_BUSINESS_LOGIN_URL', 'https://biz.worldweb-directory.com/login'),
        // Public base address of this admin app, used for email click-tracking links (/t/{token})
        'tracking_url' => env('WWD_TRACKING_URL', env('APP_URL')),
        // Public listing page on the Listing site: listing_url . wwd_url (same setting as WWD Business), {{ListingURL}}
        'listing_url' => env('WWD_LISTING_URL', 'https://worldweb-directory.com/review/'),
    ],


];

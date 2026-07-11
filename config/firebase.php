<?php

return [
    'credentials' => env('FIREBASE_CREDENTIALS'),
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'client_email' => env('FIREBASE_CLIENT_EMAIL'),
    'private_key' => env('FIREBASE_PRIVATE_KEY'),
    
    // Web client configurations
    'api_key' => env('FIREBASE_API_KEY'),
    'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),
    'app_id' => env('FIREBASE_APP_ID'),
    'vapid_key' => env('FIREBASE_VAPID_KEY'),

    // Timeout in seconds to determine if device is disconnected
    'device_connection_timeout' => (int) env('DEVICE_CONNECTION_TIMEOUT', 10),
];

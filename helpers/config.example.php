<?php
// helpers/config.example.php
//
// Sample configuration. To run the API on your machine:
//   1. Copy this file to helpers/config.php  (config.php is ignored by git)
//   2. Replace every placeholder below with your own real values
//
// Never commit helpers/config.php or put real passwords or keys in this file.

return [
    // PostgreSQL connection used by helpers/db_connect.php
    'db' => [
        'host'     => 'localhost',
        'port'     => '5432',
        'name'     => 'smilesync_database',
        'user'     => 'your_db_user',
        'password' => 'your_db_password',
    ],

    // EmailJS settings used by helpers/email_sender.php (password reset emails only)
    'emailjs' => [
        'service_id'  => 'your_emailjs_service_id',
        'public_key'  => 'your_emailjs_public_key',
        'private_key' => 'your_emailjs_private_key',
    ],
];

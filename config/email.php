<?php
// config/email.php
return [
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-email@gmail.com', // Palitan ng actual email
        'password' => 'your-app-password',    // Google App Password
        'encryption' => 'tls'
    ],
    'from' => [
        'email' => 'your-email@gmail.com',
        'name' => 'BookIT System'
    ]
];
?>
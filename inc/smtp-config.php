<?php
// inc/smtp-config.php
// SMTP Configuration for Email Notifications

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// SMTP Configuration Settings
define('SMTP_HOST', 'smtp.gmail.com');           // Gmail SMTP server
define('SMTP_PORT', 587);                         // TLS port
define('SMTP_SECURE', PHPMailer::ENCRYPTION_STARTTLS); // TLS encryption
define('SMTP_AUTH', true);                        // Enable authentication
define('SMTP_USERNAME', 'kemzywil@gmail.com');  // Your Gmail address
define('SMTP_PASSWORD', 'hxumorremugazkjx'); // App password (no spaces)
define('SMTP_FROM_EMAIL', 'kemzywil@gmail.com'); // From email
define('SMTP_FROM_NAME', 'WhistleGuard');         // From name
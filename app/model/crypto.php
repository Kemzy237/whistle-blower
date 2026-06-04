<?php

// Define encryption key (must match the one in submit-report.php)
define('ENCRYPTION_KEY', hex2bin('7a8f5c3e2d1b4a6c9e7f8d3c2b1a4f6e8d7c9a5b3e2f1c8d7a6b4f2e1c3d5a7b'));

/**
 * Decrypt data using AES-256-CBC
 */
function decryptData($encryptedData, $key) {
    $data = base64_decode($encryptedData);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * Encrypt data (for messages)
 */
function encryptData($data, $key) {
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}
<?php
// Load dependencies once
require_once __DIR__ . '/../../inc/smtp-config.php';

// Load crypto functions first
require_once __DIR__ . '/crypto.php';

// Load all models
require_once __DIR__ . '/report.php';
require_once __DIR__ . '/messages.php';
require_once __DIR__ . '/evidence.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/category.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/notification.php';
require_once __DIR__ . '/users.php';
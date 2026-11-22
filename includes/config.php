<?php
// Load secrets (DB credentials, mail credentials).
// env.php is excluded from version control and deployment uploads.
require_once __DIR__ . '/env.php';

// Base URL — no trailing slash.
// Local XAMPP: '/flexifit-project'
// Live deployment at domain root: '' (empty string)
// Live deployment at subdomain: 'https://flexifit.com'
if (!defined('BASE_URL')) {
    define('BASE_URL', 'https://flexifit.infinityfreeapp.com');
}

// Create the shared DB connection only if one doesn't exist yet.
if (!isset($conn)) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

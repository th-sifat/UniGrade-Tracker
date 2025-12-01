<?php
// db.php - simple mysqli connection
// Put this in C:\xampp\htdocs\WEB\db.php

$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '';        // change if you have a password for root
$DB_NAME = 'unigrade';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($mysqli->connect_errno) {
    // Friendly error for development. In production hide details.
    http_response_code(500);
    echo "DB connection failed: " . $mysqli->connect_error;
    exit;
}

// set charset
$mysqli->set_charset('utf8mb4');

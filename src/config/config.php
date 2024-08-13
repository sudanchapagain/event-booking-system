<?php

include 'credentials.php';

if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'client');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASSWORD') ?: 'client');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'event_booking_system');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', (int)getenv('DB_PORT') ?: 5432);
}

function getDbConnection() {
    $connectionString = sprintf(
        'host=%s port=%d dbname=%s user=%s password=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_USER,
        DB_PASS
    );

    $connection = pg_connect($connectionString);
    if (!$connection) {
        die('Error: Unable to connect to the database.');
    }

    return $connection;
}

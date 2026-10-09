<?php
declare(strict_types=1);

// The ONLY place that knows how to connect to the database.
// Every other file calls db() to get the shared PDO instance.

// Connection settings (XAMPP defaults). Change these here and nowhere else.
const DB_HOST = '127.0.0.1';
const DB_NAME = 'barangay_recipes';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    // "static" keeps the connection between calls, so we connect once per request, not once per query.
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // errors throw exceptions instead of failing quietly
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // rows come back as ['column' => value]
                PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements: user input is never glued into the SQL (stops SQL injection)
                PDO::MYSQL_ATTR_FOUND_ROWS   => true,  // rowCount() = rows matched, not just changed
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+08:00'", // keep MySQL's NOW() in Manila time too
            ]
        );
    }

    return $pdo;
}

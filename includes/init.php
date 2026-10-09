<?php
declare(strict_types=1);

// Included at the top of every page: session, autoloading, helpers, DB check, CSRF check.

// Show every date and time in Philippine time.
date_default_timezone_set('Asia/Manila');

// Safer session cookie: "httponly" keeps JavaScript from reading it,
// and "samesite" stops the browser from sending it along on requests from other sites.
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// The one database connection file, the small helper functions, and the shared page pieces (header, footer, cards).
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/layout.php';

// Autoloader: the first time code mentions a class (say, Recipe), this loads src/Recipe.php.
// That way pages never need a require line for each class.
spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/../src/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

// Try connecting right away so a stopped MySQL gives a short, friendly message
// instead of a long error trace halfway down some page.
try {
    db();
} catch (PDOException $e) {
    http_response_code(500);
    exit('Cannot reach the database. Is MySQL running and barangay_recipes imported?');
}

// Every form on the site is a POST carrying the CSRF token, so checking it here, once,
// covers every page: no page can forget to do it.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

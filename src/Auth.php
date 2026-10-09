<?php
declare(strict_types=1);

// Who is logged in. The login state lives in the session, which the server keeps per visitor.
final class Auth
{
    // Logged in = a user id is stored in the session.
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION['user_id'] : null;
    }

    public static function username(): string
    {
        return (string) ($_SESSION['username'] ?? '');
    }

    // Call right after the password has been checked.
    public static function login(int $id, string $username): void
    {
        session_regenerate_id(true); // stop session fixation
        $_SESSION['user_id']  = $id;
        $_SESSION['username'] = $username;
    }

    // Fully log out: empty the session, expire the browser's cookie, then destroy the session on the server.
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** Guard for member-only pages. Returns the logged-in user's id. */
    public static function require(): int
    {
        // This is what keeps guests out: every member page calls it first
        if (!self::check()) {
            flash('error', 'Please log in first.');
            redirect('auth.php');
        }
        return (int) $_SESSION['user_id'];
    }
}

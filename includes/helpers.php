<?php
declare(strict_types=1);

// Small functions that many pages share. Nothing here talks to the database or prints HTML.

/** Escape anything a user typed before printing it. */
function e(?string $value): string
{
    // Turns < > & " ' into harmless text, so a title like <script> is shown, not run (this is our XSS protection).
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Send the browser to another page and stop running this one.
function redirect(string $to): void
{
    header('Location: ' . $to);
    exit; // without this the rest of the page would keep running after the redirect
}

/** Only allow redirects back to our own pages (no open redirect). */
function safe_back(?string $candidate, string $default = 'index.php'): string
{
    // The "back" address comes from a form field, so it could be tampered with.
    // Accept only things shaped like "page.php?x=1#anchor"; a full URL to another site falls through to the default.
    if ($candidate !== null
        && preg_match('/^[a-z_]+\.php(\?[A-Za-z0-9_=&%+.\-]*)?(#[\w\-]+)?$/', $candidate)) {
        return $candidate;
    }
    return $default;
}

// Pages with several forms tell them apart by a hidden "action" field (e.g. "comment" or "delete").
// Returns '' on a normal visit, so a page can just compare it with each action it handles.
function post_action(): string
{
    $action = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['action'] ?? '') : '';
    return is_string($action) ? $action : '';
}

/* ---------- flash messages ---------- */

// Save a one-time message (type is "success" or "error") to show on the next page the member sees.
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

// Hand over all waiting messages and clear them, so each one only appears once. page_header() calls this.
function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ---------- CSRF ---------- */

// CSRF protection: every form carries a secret token tied to the member's session,
// so another website can't submit forms on their behalf.
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32)); // random, unguessable, made once per session
    }
    return $_SESSION['csrf'];
}

// The hidden input that goes inside every POST form.
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** init.php calls this for every POST request. */
function csrf_verify(): void
{
    $sent = $_POST['csrf'] ?? '';
    // hash_equals compares in constant time, so the token can't be guessed by timing the response
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Invalid form token. Go back, refresh the page, and try again.');
    }
}

/* ---------- formatting ---------- */

// Database timestamp -> readable text, e.g. "Oct 9, 2026 3:45 PM".
function format_date(string $timestamp): string
{
    return date('M j, Y g:i A', strtotime($timestamp));
}

/** "1/2 cup", "2 cups" - plural unit once the amount is more than one. Returns raw text; escape when printing. */
function format_amount(string $amount, string $unit): string
{
    // No unit (shouldn't happen for new recipes): just show the amount as typed.
    if ($unit === '') {
        return $amount;
    }
    // Only units that change in the plural; tsp, kg, ml and the like stay as they are.
    $plurals = ['cup' => 'cups', 'pc' => 'pcs', 'clove' => 'cloves', 'pinch' => 'pinches', 'can' => 'cans', 'pack' => 'packs', 'bunch' => 'bunches'];
    $value   = Validator::quantityValue($amount); // "1 1/2" -> 1.5, so fractions count as more than one too
    if ($value !== null && $value > 1 && isset($plurals[$unit])) {
        $unit = $plurals[$unit];
    }
    return $amount . ' ' . $unit;
}

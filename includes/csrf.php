<?php
/**
 * includes/csrf.php
 *
 * Minimal CSRF protection (report finding H-3): one random token per
 * session, embedded as a hidden field in every state-changing form (or
 * sent as an X-CSRF-Token header by AJAX calls), and verified on the
 * server before any POST handler runs.
 *
 * Include AFTER config/session.php (needs a started session).
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Echo this inside every <form method="POST"> ... </form> in the app. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function csrf_verify(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Call at the very top of any POST handler (form submit or AJAX endpoint).
 * Accepts the token from a form field OR an X-CSRF-Token header (for
 * fetch()-based AJAX calls), and stops the request with 403 if it's
 * missing or wrong.
 */
function csrf_require(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!csrf_verify($token)) {
        http_response_code(403);
        if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Security check failed (invalid CSRF token). Please refresh the page.']);
        } else {
            die('Security check failed (invalid or expired form). Please go back, refresh the page, and try again.');
        }
        exit;
    }
}

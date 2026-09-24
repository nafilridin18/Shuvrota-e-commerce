<?php
/**
 * includes/login_rate_limit.php
 *
 * Report finding H-5: a `login_attempts` table was already designed into
 * the schema (identifier, ip_address, success, attempted_at) but nothing
 * ever wrote to it, so there was no brute-force protection on either
 * login form. This wires that table up.
 *
 * Policy: after MAX_FAILED_ATTEMPTS failed attempts (by the same
 * identifier — phone or admin email — OR the same IP) within
 * LOCKOUT_WINDOW_MINUTES minutes, further attempts are blocked until the
 * window rolls off. Successful logins don't reset the counter early;
 * they just don't add to it.
 */

const LOGIN_MAX_FAILED_ATTEMPTS   = 5;
const LOGIN_LOCKOUT_WINDOW_MINUTES = 15;

function login_client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** True if this identifier or this IP has hit the failed-attempt limit recently. */
function login_is_locked_out(PDO $pdo, string $identifier, bool $isAdmin): bool
{
    $ip = login_client_ip();

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE is_admin_login = ? AND success = 0
           AND attempted_at > (NOW() - INTERVAL ? MINUTE)
           AND (identifier = ? OR ip_address = ?)"
    );
    $stmt->execute([$isAdmin ? 1 : 0, LOGIN_LOCKOUT_WINDOW_MINUTES, $identifier, $ip]);

    return (int) $stmt->fetchColumn() >= LOGIN_MAX_FAILED_ATTEMPTS;
}

function login_record_attempt(PDO $pdo, string $identifier, bool $isAdmin, bool $success): void
{
    $ip = login_client_ip();
    $pdo->prepare(
        "INSERT INTO login_attempts (identifier, ip_address, is_admin_login, success) VALUES (?, ?, ?, ?)"
    )->execute([$identifier, $ip, $isAdmin ? 1 : 0, $success ? 1 : 0]);

    // Occasionally prune old rows so this table doesn't grow forever.
    if (random_int(1, 50) === 1) {
        $pdo->exec("DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 7 DAY)");
    }
}

function login_lockout_message(): string
{
    return "অনেকবার ভুল চেষ্টা করা হয়েছে। " . LOGIN_LOCKOUT_WINDOW_MINUTES . " মিনিট পর আবার চেষ্টা করুন। / Too many failed attempts. Please try again in " . LOGIN_LOCKOUT_WINDOW_MINUTES . " minutes.";
}

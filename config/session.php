<?php
/**
 * config/session.php
 *
 * Centralized, hardened session bootstrap. Every page should
 * require_once this file INSTEAD of calling session_start() directly,
 * so the whole app shares the same secure cookie settings.
 *
 * Why this matters once HTTPS is mandatory (see .htaccess):
 *  - 'secure'   => cookie is only ever sent over an HTTPS connection.
 *                  Detected automatically, so this stays safe to include
 *                  on local XAMPP (plain HTTP) too — it just won't set
 *                  the secure flag there.
 *  - 'httponly' => JavaScript can never read the session cookie, which
 *                  blocks session-hijacking even if an XSS bug slips in.
 *  - 'samesite' => 'Lax' stops the cookie being sent on most cross-site
 *                  requests, which helps against CSRF too (defense in depth,
 *                  not a replacement for real CSRF tokens).
 */

if (session_status() === PHP_SESSION_NONE) {

    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'); // some cPanel setups terminate SSL upstream

    session_set_cookie_params([
        'lifetime' => 0,        // session cookie, expires when the browser closes
        'path'     => '/',
        'domain'   => '',       // current domain only
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

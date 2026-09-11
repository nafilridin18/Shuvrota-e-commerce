<?php
/**
 * ONE-TIME USE ONLY.
 *
 * Run this once in your browser (e.g. http://localhost/.../admin/fix_admin_password_ONETIME.php)
 * to convert any plaintext admin password into a proper bcrypt hash.
 * It is safe to run more than once — it skips rows that are already hashed.
 *
 * AFTER YOU CONFIRM IT WORKED, DELETE THIS FILE. Do not deploy it to a live server.
 */

require_once '../config/database.php';

echo "<pre>";

$admins = $pdo->query("SELECT id, email, password_hash FROM admins")->fetchAll();

foreach ($admins as $admin) {
    $hash = $admin['password_hash'];
    $looksHashed = (bool) preg_match('/^\$2[aby]\$/', $hash);

    if ($looksHashed) {
        echo "SKIP  (already hashed): {$admin['email']}\n";
        continue;
    }

    // At this point $hash is actually the plaintext password stored by mistake.
    $newHash = password_hash($hash, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?")
        ->execute([$newHash, $admin['id']]);

    echo "FIXED (was plaintext):  {$admin['email']}  -> now bcrypt hashed\n";
    echo "        Log in with the SAME password you used before ({$hash}) — it still works,\n";
    echo "        it's just stored securely now. Change it from the admin panel afterward.\n";
}

echo "\nDone. DELETE THIS FILE NOW (admin/fix_admin_password_ONETIME.php).\n";
echo "</pre>";

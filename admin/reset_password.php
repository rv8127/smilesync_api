<?php
// admin/reset_password.php -- Step 2 of System Admin password recovery.
// POST { email, code, password }.
//
// Verifies the submitted code against the hash forgot_password.php stored
// (and that it hasn't expired), then updates the password and clears the
// used code so it can't be replayed.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/log_activity.php';

$data = get_json_body();
$data = validate_or_fail($data, [
    'email'    => ['required' => true, 'type' => 'email'],
    'code'     => ['required' => true, 'type' => 'string'],
    'password' => ['required' => true, 'type' => 'string', 'minlen' => 8],
]);

$conn = getConnection();

$result = pg_query_params($conn,
    "SELECT admin_id, login_email, reset_code_hash, reset_code_expires_at FROM system_admin WHERE login_email = $1",
    array($data['email'])
);
fail_on_db_error($result, 'admin/reset_password: lookup', $conn);
$admin = pg_fetch_assoc($result);

// Same generic 401 for "no such account", "no code was ever requested",
// "wrong code", and "expired code" -- never tells the caller which of
// those it actually was.
$rejectCode = function () {
    send_error(401, "That verification code is invalid or has expired. Please request a new one.", ["reason" => "invalid_reset_code"]);
};

if (!$admin || empty($admin['reset_code_hash'])) {
    $rejectCode();
}
if (empty($admin['reset_code_expires_at']) || strtotime($admin['reset_code_expires_at']) < time()) {
    $rejectCode();
}
if (!password_verify($data['code'], $admin['reset_code_hash'])) {
    $rejectCode();
}

$newHash = password_hash($data['password'], PASSWORD_DEFAULT);
$updateResult = pg_query_params($conn,
    "UPDATE system_admin
     SET password_hash = $1, reset_code_hash = NULL, reset_code_expires_at = NULL
     WHERE admin_id = $2",
    array($newHash, $admin['admin_id'])
);
fail_on_db_error($updateResult, 'admin/reset_password: update', $conn);

log_activity(
    $conn, (int) $admin['admin_id'], $admin['login_email'], 'admin_password_reset',
    'system_admin', (int) $admin['admin_id'],
    "{$admin['login_email']}'s password was reset via email verification"
);

send_success(["message" => "Password updated. You can now sign in with your new password."]);

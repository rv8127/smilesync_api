<?php
// auth/reset_password.php -- Step 2 of clinic staff password recovery.
// POST { email, code, password }.
//
// Verifies the submitted code against the hash
// forgot_password.php stored (and that it hasn't expired), then updates
// the password and clears the used code so it can't be replayed.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();
$data = validate_or_fail($data, [
    'email'    => ['required' => true, 'type' => 'email'],
    'code'     => ['required' => true, 'type' => 'string'],
    'password' => ['required' => true, 'type' => 'string', 'minlen' => 8],
]);

$conn = getConnection();

$result = pg_query_params($conn,
    "SELECT clinic_id, login_email, reset_code_hash, reset_code_expires_at FROM clinic_account WHERE login_email = $1",
    array($data['email'])
);
fail_on_db_error($result, 'auth/reset_password: lookup', $conn);
$account = pg_fetch_assoc($result);

// Same generic 401 for "no such account", "no code was ever requested",
// "wrong code", and "expired code" -- never tells the caller which of
// those it actually was.
$rejectCode = function () {
    send_error(401, "That verification code is invalid or has expired. Please request a new one.", ["reason" => "invalid_reset_code"]);
};

if (!$account || empty($account['reset_code_hash'])) {
    $rejectCode();
}
if (empty($account['reset_code_expires_at']) || strtotime($account['reset_code_expires_at']) < time()) {
    $rejectCode();
}
if (!password_verify($data['code'], $account['reset_code_hash'])) {
    $rejectCode();
}

$newHash = password_hash($data['password'], PASSWORD_DEFAULT);
$updateResult = pg_query_params($conn,
    "UPDATE clinic_account
     SET password_hash = $1, reset_code_hash = NULL, reset_code_expires_at = NULL
     WHERE clinic_id = $2 AND login_email = $3",
    array($newHash, $account['clinic_id'], $account['login_email'])
);
fail_on_db_error($updateResult, 'auth/reset_password: update', $conn);

send_success(["message" => "Password updated. You can now sign in with your new password."]);

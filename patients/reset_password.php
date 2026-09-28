<?php
// patients/reset_password.php -- Step 3 of patient password recovery.
// POST { email, code, password }.
//
// Mirrors auth/reset_password.php exactly, but updates patient_account
// instead of clinic_account. Re-verifies the code + expiry itself (never
// trusts that verify_reset_code.php was called first), then updates the
// password and clears the used code so it can't be replayed.
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
    "SELECT patient_account_id, login_email, reset_code_hash, reset_code_expires_at FROM patient_account WHERE login_email = $1",
    array($data['email'])
);
fail_on_db_error($result, 'patients/reset_password: lookup', $conn);
$account = pg_fetch_assoc($result);

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
    "UPDATE patient_account
     SET password_hash = $1, reset_code_hash = NULL, reset_code_expires_at = NULL
     WHERE patient_account_id = $2",
    array($newHash, $account['patient_account_id'])
);
fail_on_db_error($updateResult, 'patients/reset_password: update', $conn);

send_success(["message" => "Password updated. You can now sign in with your new password."]);

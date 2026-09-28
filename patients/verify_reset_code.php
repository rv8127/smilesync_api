<?php
// patients/verify_reset_code.php -- Step 2 of patient password recovery.
// POST { email, code }.
//
// The staff web flow only has 2 steps (request code -> code+new password
// together), but the patient app has a dedicated OTP screen, so this
// endpoint lets that screen give immediate "wrong/expired code" feedback
// before the patient bothers typing a new password. Does NOT clear the
// code -- reset_password.php re-verifies it independently, since a
// client-side "verified" flag can't be trusted for the actual password
// change.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();
$data = validate_or_fail($data, [
    'email' => ['required' => true, 'type' => 'email'],
    'code'  => ['required' => true, 'type' => 'string'],
]);

$conn = getConnection();

$result = pg_query_params($conn,
    "SELECT reset_code_hash, reset_code_expires_at FROM patient_account WHERE login_email = $1",
    array($data['email'])
);
fail_on_db_error($result, 'patients/verify_reset_code: lookup', $conn);
$account = pg_fetch_assoc($result);

// Same generic rejection for "no such account", "no code was ever
// requested", "wrong code", and "expired code" -- mirrors
// auth/reset_password.php's $rejectCode pattern.
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

send_success(["message" => "Code verified."]);

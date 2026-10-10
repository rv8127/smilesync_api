<?php
// patients/forgot_password.php -- Step 1 of patient password recovery.
// POST { email }.
//
// Mirrors auth/forgot_password.php, but looks up patient_account (joined to
// patient + clinic, same shape as patients/login.php) instead of
// clinic_account, and sends the email itself server-side via EmailJS's REST
// API (helpers/email_sender.php) instead of handing the plaintext code back
// to the frontend -- the Android app has no safe way to send it client-side
// the way forgot-password.html does in a browser.
// Unregistered / ineligible emails are rejected with a reason (account_not_found,
// patient_archived, inactive_account, clinic_inactive) so the app can flag them.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/email_sender.php';

// TODO: set once the patient-facing template is created in EmailJS (see
// chat notes on cloning vs. reusing the staff template).
const PATIENT_RESET_TEMPLATE_ID = 'template_d6n9k85';

$data = get_json_body();
$data = validate_or_fail($data, [
    'email' => ['required' => true, 'type' => 'email'],
]);

$conn = getConnection();

$result = pg_query_params($conn,
    "SELECT pa.patient_account_id, pa.login_email, pa.account_status,
            p.first_name, p.clinic_id, p.is_archived,
            c.status AS clinic_status, c.tier
     FROM patient_account pa
     JOIN patient p ON p.patient_id = pa.patient_id
     JOIN clinic c ON c.clinic_id = p.clinic_id
     WHERE pa.login_email = $1",
    array($data['email'])
);
fail_on_db_error($result, 'patients/forgot_password: lookup', $conn);
$account = pg_fetch_assoc($result);

// Only real, eligible patient accounts get a code. Anything else is flagged back to the
// app (instead of the old generic "codeSent: false" success) so the patient is told right
// away rather than waiting on an email that will never come. Trade-off: this reveals whether
// an email has a patient account -- same as patients/login.php already does for "no clinic".
if (!$account || strtolower($account['tier']) !== 'pro') {
    send_error(404, "No account found for this email.", ["reason" => "account_not_found"]);
}
if ($account['is_archived'] === 't' || $account['is_archived'] === true) {
    send_error(403, "Your account has been archived by the clinic.", ["reason" => "patient_archived"]);
}
if ($account['account_status'] !== 'active') {
    send_error(401, "This account is not active", ["reason" => "inactive_account"]);
}
if ($account['clinic_status'] !== 'active') {
    send_error(403, "This clinic has been deactivated.", ["reason" => "clinic_inactive"]);
}

$code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$codeHash = password_hash($code, PASSWORD_DEFAULT);

$updateResult = pg_query_params($conn,
    "UPDATE patient_account
     SET reset_code_hash = $1, reset_code_expires_at = NOW() + INTERVAL '15 minutes'
     WHERE patient_account_id = $2",
    array($codeHash, $account['patient_account_id'])
);
fail_on_db_error($updateResult, 'patients/forgot_password: store code', $conn);

$emailResult = send_via_emailjs(PATIENT_RESET_TEMPLATE_ID, [
    'to_email'   => $account['login_email'],
    'first_name' => $account['first_name'],
    'code'       => $code,
]);

if (!$emailResult['success']) {
    // Log the real reason for ourselves; the patient only needs to know it didn't go out,
    // otherwise the app would move on to the code screen for an email that never arrives.
    error_log('EmailJS send failed for patient reset: ' . $emailResult['message']);
    send_error(500, "Couldn't send the code. Please try again.", ["reason" => "email_failed"]);
}

send_success(["codeSent" => true]);

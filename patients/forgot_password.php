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
            p.first_name, p.clinic_id,
            c.status AS clinic_status, c.tier
     FROM patient_account pa
     JOIN patient p ON p.patient_id = pa.patient_id
     JOIN clinic c ON c.clinic_id = p.clinic_id
     WHERE pa.login_email = $1",
    array($data['email'])
);
fail_on_db_error($result, 'patients/forgot_password: lookup', $conn);
$account = pg_fetch_assoc($result);

// Same generic response either way -- never reveals whether the email
// matched an account, same trade-off auth/forgot_password.php documents.
$response = ["codeSent" => false];

$eligible = $account
    && $account['account_status'] === 'active'
    && $account['clinic_status'] === 'active'
    && strtolower($account['tier']) === 'pro';

if ($eligible) {
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
        // Log for ourselves; still don't leak details to the client, and
        // still don't flip codeSent -- the code is now stored but useless
        // to the patient if the email never arrived, so treat it the same
        // as "not eligible" from the caller's point of view.
        error_log('EmailJS send failed for patient reset: ' . $emailResult['message']);
    } else {
        $response = ["codeSent" => true];
    }
}

send_success($response);

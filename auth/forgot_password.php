<?php
// auth/forgot_password.php -- Step 1 of clinic staff password recovery.
// POST { email }.
//
// Generates a 6-digit code and stores its hash +
// a 15-minute expiry on that account row 
// when the email matches an active staff account at an active clinic.
// Delivery happens client-side via EmailJS 

require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();
$data = validate_or_fail($data, [
    'email' => ['required' => true, 'type' => 'email'],
]);

$conn = getConnection();

$result = pg_query_params($conn,
    "SELECT ca.clinic_id, ca.login_email, ca.account_status,
            c.clinic_name, c.status AS clinic_status
     FROM clinic_account ca
     JOIN clinic c ON c.clinic_id = ca.clinic_id
     WHERE ca.login_email = $1",
    array($data['email'])
);
fail_on_db_error($result, 'auth/forgot_password: lookup', $conn);
$account = pg_fetch_assoc($result);

$response = ["codeSent" => false];

if ($account && $account['account_status'] === 'active' && $account['clinic_status'] === 'active') {
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $codeHash = password_hash($code, PASSWORD_DEFAULT);

    $updateResult = pg_query_params($conn,
        "UPDATE clinic_account
         SET reset_code_hash = $1, reset_code_expires_at = NOW() + INTERVAL '15 minutes'
         WHERE clinic_id = $2 AND login_email = $3",
        array($codeHash, $account['clinic_id'], $account['login_email'])
    );
    fail_on_db_error($updateResult, 'auth/forgot_password: store code', $conn);

    // Handed to the frontend so it can call EmailJS -- see the trade-off
    // note in the file header comment above.
    $response = [
        "codeSent" => true,
        "code" => $code,
        "clinicName" => $account['clinic_name'],
    ];
}

send_success($response);

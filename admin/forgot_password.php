<?php
// admin/forgot_password.php -- Step 1 of System Admin password recovery.
// POST { email }.
//
// Generates a 6-digit code and stores its hash + a 15-minute expiry on that
// admin row (see migration_add_password_reset.sql) when the email matches
// an active account. Actual delivery happens client-side via EmailJS (see
// forgot-password.html) -- not server-side mail() -- because XAMPP has no
// SMTP relay configured by default. That means this endpoint has to hand
// the plaintext code back to the frontend so it can pass it to EmailJS,
// which is a deliberate, documented trade-off: it's less airtight than a
// server-only email flow, and "codeSent" below does reveal whether the
// email matched a real account. Acceptable for this project; worth knowing
// if this code is ever reused somewhere more sensitive.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/log_activity.php';

$data = get_json_body();
$data = validate_or_fail($data, [
    'email' => ['required' => true, 'type' => 'email'],
]);

$conn = getConnection();

$result = pg_query_params($conn,
    "SELECT admin_id, first_name, login_email, account_status FROM system_admin WHERE login_email = $1",
    array($data['email'])
);
fail_on_db_error($result, 'admin/forgot_password: lookup', $conn);
$admin = pg_fetch_assoc($result);

$response = ["codeSent" => false];

if ($admin && $admin['account_status'] === 'active') {
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $codeHash = password_hash($code, PASSWORD_DEFAULT);

    $updateResult = pg_query_params($conn,
        "UPDATE system_admin
         SET reset_code_hash = $1, reset_code_expires_at = NOW() + INTERVAL '15 minutes'
         WHERE admin_id = $2",
        array($codeHash, $admin['admin_id'])
    );
    fail_on_db_error($updateResult, 'admin/forgot_password: store code', $conn);

    log_activity(
        $conn, (int) $admin['admin_id'], $admin['login_email'], 'admin_password_reset_requested',
        'system_admin', (int) $admin['admin_id'],
        "Password reset code requested for {$admin['login_email']}"
    );

    // Handed to the frontend so it can call EmailJS -- see the trade-off
    // note in the file header comment above.
    $response = [
        "codeSent" => true,
        "code" => $code,
        "firstName" => $admin['first_name'],
    ];
}

send_success($response);


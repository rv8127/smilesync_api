<?php
// auth/login.php -- clinic staff login
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'email'    => ['required' => true, 'type' => 'string'],
    'password' => ['required' => true, 'type' => 'string'],
]);

$conn = getConnection();

$result = pg_query_params($conn,
    "SELECT ca.clinic_id, ca.password_hash, ca.account_status,
            c.clinic_code, c.clinic_name, c.tier, c.status AS clinic_status,
            (c.subscription_end - (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Manila')::date) AS days_left
     FROM clinic_account ca
     JOIN clinic c ON c.clinic_id = ca.clinic_id
     WHERE ca.login_email = $1",
    array($data['email'])
);
fail_on_db_error($result, 'auth/login: lookup', $conn);
$account = pg_fetch_assoc($result);

if (!$account || !password_verify($data['password'], $account['password_hash'])) {
    send_error(401, "Invalid email or password");
}

if ($account['account_status'] !== 'active') {
    send_error(401, "This account is not active", ["reason" => "inactive_account"]);
}

// Distinct from the staff account's own status above -- this is whether
// System Admin has deactivated the *clinic itself*. Checked here (in
// addition to require_clinic() gating every other endpoint) so a
// deactivated clinic's staff get a clear message right at sign-in instead
// of logging in successfully and only then hitting errors everywhere else.
if ($account['clinic_status'] !== 'active') {
    send_error(403, "This clinic has been deactivated. Contact SmileSync support to reactivate it.", ["reason" => "clinic_inactive"]);
}

// Subscription ran out (past its due date): the web admin is temporarily locked until System Admin
// records the clinic's payment, which extends the due date and unlocks it automatically.
// A clinic with no due date set is never locked.
if ($account['days_left'] !== null && (int) $account['days_left'] < 0) {
    send_error(403, "Your clinic's subscription has expired, so the web admin is temporarily locked. Contact SmileSync to renew it.", ["reason" => "subscription_expired"]);
}

session_start();
$_SESSION['clinic_id'] = $account['clinic_id'];

send_success([
    "message" => "Login successful",
    "clinicId" => $account['clinic_id'],
    "clinicCode" => $account['clinic_code'],
    "clinicName" => $account['clinic_name'],
    "tier" => $account['tier']
]);

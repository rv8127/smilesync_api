<?php
// patients/login.php -- patient app login - FOR PATIENTS ONLY, WITH CLINIC HAVING PRO TIER (Basic-tier clinics never get a clinic code to hand out in the first place, only its for them to verify their own clinic, but not for patients, so this endpoint is Pro-tier-only)
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
    "SELECT pa.patient_account_id, pa.password_hash, pa.account_status,
            p.patient_id, p.first_name, p.last_name, p.clinic_id,
            c.clinic_code, c.clinic_name, c.status AS clinic_status, c.tier
     FROM patient_account pa
     JOIN patient p ON p.patient_id = pa.patient_id
     JOIN clinic c ON c.clinic_id = p.clinic_id
     WHERE pa.login_email = $1",
    array($data['email'])
);
fail_on_db_error($result, 'patients/login: lookup', $conn);
$account = pg_fetch_assoc($result);

if (!$account || !password_verify($data['password'], $account['password_hash'])) {
    send_error(401, "Invalid email or password");
}

if ($account['account_status'] !== 'active') {
    send_error(401, "This account is not active", ["reason" => "inactive_account"]);
}

// Same clinic-level deactivation check as auth/login.php (clinic staff) --
// if System Admin deactivated the clinic itself, its patients shouldn't be
// able to sign into the mobile app either.
if ($account['clinic_status'] !== 'active') {
    send_error(403, "This clinic has been deactivated. Contact SmileSync support to reactivate it.", ["reason" => "clinic_inactive"]);
}

// Patient app is a Pro-tier-only feature. A Basic-tier clinic is never
// supposed to hand out a clinic code to patients in the first place, but
// that's a business-level control only -- this is the actual technical
// enforcement, and it deliberately reads as "no clinic found" rather than
// confirming the account exists under a Basic clinic.
if (strtolower($account['tier']) !== 'pro') {
    send_error(404, "No clinic found for this account.");
}

session_start();
$_SESSION['patient_account_id'] = $account['patient_account_id'];
$_SESSION['patient_id'] = $account['patient_id'];

send_success([
    "message" => "Login successful",
    "patientAccountId" => $account['patient_account_id'],
    "patientId" => $account['patient_id'],
    "fullName" => $account['first_name'] . ' ' . $account['last_name'],
    "clinicId" => $account['clinic_id'],
    "clinicCode" => $account['clinic_code'],
    "clinicName" => $account['clinic_name']
]);

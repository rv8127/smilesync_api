<?php
// patients/register.php - patient app registration - FOR PATIENTS ONLY, WITH CLINIC HAVING PRO TIER (Basic-tier clinics never get a clinic code to hand out in the first place, only its for them to verify their own clinic, but not for patients, so this endpoint is Pro-tier-only)
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'clinicCode'   => ['required' => true, 'type' => 'string'],
    'contactEmail' => ['required' => true, 'type' => 'string'], // matched raw vs DB, not format-checked in original
    'loginEmail'   => ['required' => true, 'type' => 'email'],
    'password'     => ['required' => true, 'type' => 'string', 'minlen' => 8],
]);

$conn = getConnection();
$clinic = require_clinic($conn, $data['clinicCode']);
require_pro_tier_clinic($clinic);

$patientResult = pg_query_params($conn,
    "SELECT patient_id, first_name, last_name FROM patient
     WHERE clinic_id = $1 AND contact_email = $2",
    array($clinic['clinic_id'], $data['contactEmail'])
);
fail_on_db_error($patientResult, 'patients/register: patient lookup', $conn);
$patient = pg_fetch_assoc($patientResult);

if (!$patient) {
    send_error(404, "No matching patient record found. Please register in person at the clinic first.");
}

$dupResult = pg_query_params($conn,
    "SELECT patient_account_id FROM patient_account WHERE patient_id = $1 OR login_email = $2",
    array($patient['patient_id'], $data['loginEmail'])
);
fail_on_db_error($dupResult, 'patients/register: duplicate check', $conn);

if (pg_num_rows($dupResult) > 0) {
    send_error(422, "An account already exists for this patient or email", ["reason" => "duplicate"]);
}

$hash = password_hash($data['password'], PASSWORD_DEFAULT);

$insertResult = pg_query_params($conn,
    "INSERT INTO patient_account (patient_id, login_email, password_hash, account_status)
     VALUES ($1, $2, $3, 'active') RETURNING patient_account_id",
    array($patient['patient_id'], $data['loginEmail'], $hash)
);
fail_on_db_error($insertResult, 'patients/register: insert', $conn);

$newAccount = pg_fetch_assoc($insertResult);

send_success([
    "message" => "Account created successfully",
    "patientAccountId" => $newAccount['patient_account_id'],
    "fullName" => $patient['first_name'] . ' ' . $patient['last_name']
]);

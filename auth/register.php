<?php
// auth/register.php -- clinic staff account creation.
//
// A new staff member signs up with the unique clinic code their System
// Admin issued when the clinic was registered (see admin/add_clinic.php),
// plus an email. This creates a new clinic_account row tied to
// that clinic and logs the staff member straight in, mirroring
// auth/login.php's session handling and response shape exactly so the
// frontend can reuse the same "on success" handling code. 

// But clinic or clinic staff will create their own password during account creation for security reasons

require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'clinicCode' => ['required' => true, 'type' => 'string'],
    'email'      => ['required' => true, 'type' => 'email'],
    'password'   => ['required' => true, 'type' => 'string', 'minlen' => 8],
]);

$conn = getConnection();

// Look up the clinic by its code (not using the shared require_clinic()
// helper here since we also need status + name/code back for the response,
// not just clinic_id).
$clinicResult = pg_query_params($conn,
    "SELECT clinic_id, clinic_code, clinic_name, status FROM clinic WHERE clinic_code = $1",
    array($data['clinicCode'])
);
fail_on_db_error($clinicResult, 'auth/register: clinic lookup', $conn);
$clinic = pg_fetch_assoc($clinicResult);

if (!$clinic) {
    send_error(404, "No clinic found with this code. Double-check it with your System Admin.", ["clinicCode" => "not found"]);
}
if ($clinic['status'] !== 'active') {
    send_error(403, "This clinic isn't active. Contact SmileSync support.", ["reason" => "clinic_inactive"]);
}

// Duplicate email check. Selecting a literal 1 rather than a specific
// id column since clinic_account's primary-key column name isn't
// confirmed anywhere else in this API.
$dupResult = pg_query_params($conn, "SELECT 1 FROM clinic_account WHERE login_email = $1", array($data['email']));
fail_on_db_error($dupResult, 'auth/register: duplicate check', $conn);
if (pg_num_rows($dupResult) > 0) {
    send_error(422, "An account with this email already exists", ["email" => "already in use"]);
}

$hash = password_hash($data['password'], PASSWORD_DEFAULT);

$insertResult = pg_query_params($conn,
    "INSERT INTO clinic_account (clinic_id, login_email, password_hash, account_status)
     VALUES ($1, $2, $3, 'active')",
    array($clinic['clinic_id'], $data['email'], $hash)
);
fail_on_db_error($insertResult, 'auth/register: insert', $conn);

// Log the new staff member straight in, same session shape as auth/login.php.
session_start();
$_SESSION['clinic_id'] = $clinic['clinic_id'];

send_success([
    "message" => "Account created successfully",
    "clinicId" => $clinic['clinic_id'],
    "clinicCode" => $clinic['clinic_code'],
    "clinicName" => $clinic['clinic_name']
], 201);

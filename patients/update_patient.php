<?php
// patients/update_patient.php - patient app update patient info - FOR PATIENTS ONLY, WITH CLINIC HAVING PRO TIER (Basic-tier clinics never get a clinic code to hand out in the first place, only its for them to verify their own clinic, but not for patients, so this endpoint is Pro-tier-only)
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'clinicCode'   => ['required' => true, 'type' => 'string'],
    'patientId'    => ['required' => true, 'type' => 'int'],
    'firstName'    => ['type' => 'string'],
    'lastName'     => ['type' => 'string'],
    'phone'        => ['type' => 'string'],
    'address'      => ['type' => 'string'],
    'contactEmail' => ['type' => 'string'], // matched raw, not format-checked, consistent with add_patient.php
    'birthdate'    => ['type' => 'date'],
    'gender'       => ['type' => 'string'],
    // isArchived is a boolean, handled separately below (not covered by validate_or_fail's type system)
]);

$conn = getConnection();
$clinic = require_clinic($conn, $data['clinicCode']);

$existingResult = pg_query_params($conn,
    "SELECT patient_id, first_name, last_name, contact_number, address, contact_email, birthdate, gender, is_archived
     FROM patient WHERE patient_id = $1 AND clinic_id = $2",
    array($data['patientId'], $clinic['clinic_id'])
);
fail_on_db_error($existingResult, 'patients/update_patient: lookup', $conn);
$existing = pg_fetch_assoc($existingResult);

// Same "not found" whether the id is missing or belongs to another clinic.
if (!$existing) {
    send_error(404, "Patient not found");
}

// Only fields actually provided are changed; anything omitted keeps its current value.
$firstName    = $data['firstName']    ?? $existing['first_name'];
$lastName     = $data['lastName']     ?? $existing['last_name'];
$phone        = $data['phone']        ?? $existing['contact_number'];
$address      = $data['address']      ?? $existing['address'];
$contactEmail = $data['contactEmail'] ?? $existing['contact_email'];
$birthdate    = $data['birthdate']    ?? $existing['birthdate'];
$gender       = $data['gender']       ?? $existing['gender'];

$isArchived = array_key_exists('isArchived', $data)
    ? filter_var($data['isArchived'], FILTER_VALIDATE_BOOLEAN)
    : ($existing['is_archived'] === 't' || $existing['is_archived'] === true);

$updateResult = pg_query_params($conn,
    "UPDATE patient SET first_name = $1, last_name = $2, contact_number = $3, address = $4,
                        contact_email = $5, birthdate = $6, gender = $7, is_archived = $8::boolean
     WHERE patient_id = $9 AND clinic_id = $10",
    array($firstName, $lastName, $phone, $address, $contactEmail, $birthdate, $gender,
          $isArchived ? 'true' : 'false', $data['patientId'], $clinic['clinic_id'])
);
fail_on_db_error($updateResult, 'patients/update_patient: update', $conn);

send_success([
    "message" => "Profile updated successfully",
    "fullName" => $firstName . ' ' . $lastName
]);

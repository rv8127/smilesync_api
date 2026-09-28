<?php
// patients/add_patient.php - Endpoint to add a new patient to a clinic
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'clinicCode'    => ['required' => true, 'type' => 'string'],
    'firstName'     => ['required' => true, 'type' => 'string'],
    'lastName'      => ['required' => true, 'type' => 'string'],
    'contactEmail'  => ['type' => 'string'],
    'contactNumber' => ['type' => 'string'],
    'address'       => ['type' => 'string'],
    'birthdate'     => ['type' => 'date'],
    'gender'        => ['type' => 'string'],
]);

$conn = getConnection();
$clinic = require_clinic($conn, $data['clinicCode']);

if (!empty($data['contactEmail'])) {
    $dupResult = pg_query_params($conn,
        "SELECT patient_id FROM patient WHERE clinic_id = $1 AND contact_email = $2",
        array($clinic['clinic_id'], $data['contactEmail'])
    );
    fail_on_db_error($dupResult, 'patients/add_patient: duplicate check', $conn);

    if (pg_num_rows($dupResult) > 0) {
        send_error(422, "A patient with this email already exists at this clinic", ["reason" => "duplicate"]);
    }
}

$insertResult = pg_query_params($conn,
    "INSERT INTO patient (clinic_id, first_name, last_name, contact_email, contact_number, address, birthdate, gender)
     VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING patient_id",
    array(
        $clinic['clinic_id'],
        $data['firstName'],
        $data['lastName'],
        $data['contactEmail'] ?? null,
        $data['contactNumber'] ?? null,
        $data['address'] ?? null,
        $data['birthdate'] ?? null,
        $data['gender'] ?? null
    )
);
fail_on_db_error($insertResult, 'patients/add_patient: insert', $conn);

$newPatient = pg_fetch_assoc($insertResult);

send_success([
    "message" => "Patient added successfully",
    "patientId" => $newPatient['patient_id']
]);

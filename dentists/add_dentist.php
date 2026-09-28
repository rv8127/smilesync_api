<?php
// dentists/add_dentist.php - adds dentist to a clinic web admin 
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'clinicCode'     => ['required' => true, 'type' => 'string'],
    'firstName'      => ['required' => true, 'type' => 'string'],
    'lastName'       => ['required' => true, 'type' => 'string'],
    'specialization' => ['type' => 'string'],
]);

$conn = getConnection();
$clinic = require_clinic($conn, $data['clinicCode']);

$insertResult = pg_query_params($conn,
    "INSERT INTO dentist (clinic_id, first_name, last_name, specialization, status)
     VALUES ($1, $2, $3, $4, 'active') RETURNING dentist_id",
    array(
        $clinic['clinic_id'],
        $data['firstName'],
        $data['lastName'],
        $data['specialization'] ?? null
    )
);
fail_on_db_error($insertResult, 'dentists/add_dentist: insert', $conn);

$newDentist = pg_fetch_assoc($insertResult);

send_success([
    "message" => "Dentist added successfully",
    "dentistId" => $newDentist['dentist_id']
]);

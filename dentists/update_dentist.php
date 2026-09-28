<?php
// dentists/update_dentist.php - updates dentist details for a clinic web admin
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'dentistId'      => ['required' => true, 'type' => 'int'],
    'firstName'      => ['type' => 'string'],
    'lastName'       => ['type' => 'string'],
    'specialization' => ['type' => 'string'],
    'status'         => ['type' => 'string'],
]);

$conn = getConnection();

$existingResult = pg_query_params($conn,
    "SELECT dentist_id, first_name, last_name, specialization, status FROM dentist WHERE dentist_id = $1",
    array($data['dentistId'])
);
fail_on_db_error($existingResult, 'dentists/update_dentist: lookup', $conn);
$existing = pg_fetch_assoc($existingResult);

if (!$existing) {
    send_error(404, "Dentist not found");
}

$firstName = $data['firstName'] ?? $existing['first_name'];
$lastName  = $data['lastName']  ?? $existing['last_name'];
$specialization = $data['specialization'] ?? $existing['specialization'];
$status = $data['status'] ?? $existing['status'];

$updateResult = pg_query_params($conn,
    "UPDATE dentist SET first_name = $1, last_name = $2, specialization = $3, status = $4 WHERE dentist_id = $5",
    array($firstName, $lastName, $specialization, $status, $data['dentistId'])
);
fail_on_db_error($updateResult, 'dentists/update_dentist: update', $conn);

send_success(["message" => "Dentist updated successfully"]);

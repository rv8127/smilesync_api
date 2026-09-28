<?php
// appointments/delete_appointment.php - deletes an appointment from the database. This is a hard delete, 
// and should only be used in exceptional circumstances (e.g. test data cleanup). In normal operation, 
// appointments should be cancelled instead of deleted.

require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'appointmentId' => ['required' => true, 'type' => 'int'],
]);

$conn = getConnection();

$existingResult = pg_query_params($conn,
    "SELECT appointment_id FROM appointment WHERE appointment_id = $1",
    array($data['appointmentId'])
);
fail_on_db_error($existingResult, 'appointments/delete_appointment: lookup', $conn);

if (!pg_fetch_assoc($existingResult)) {
    send_error(404, "Appointment not found");
}

$result = pg_query_params($conn,
    "DELETE FROM appointment WHERE appointment_id = $1",
    array($data['appointmentId'])
);
fail_on_db_error($result, 'appointments/delete_appointment: delete', $conn);

send_success(["message" => "Appointment deleted"]);

<?php
// appointments/cancel_appointment.php -- patient cancels their own appointment via the app - 
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'appointmentId' => ['required' => true, 'type' => 'int'],
    'patientId'     => ['required' => true, 'type' => 'int'],
]);

$conn = getConnection();

// Ownership check - a patient can only cancel their own appointment.
$apptResult = pg_query_params($conn,
    "SELECT appointment_id, status, clinic_id FROM appointment WHERE appointment_id = $1 AND patient_id = $2",
    array($data['appointmentId'], $data['patientId'])
);
fail_on_db_error($apptResult, 'appointments/cancel_appointment: lookup', $conn);
$appt = pg_fetch_assoc($apptResult);

if (!$appt) {
    send_error(404, "Appointment not found");
}

require_clinic_active_by_id($conn, (int) $appt['clinic_id']);

if ($appt['status'] === 'completed' || $appt['status'] === 'cancelled') {
    send_error(422, "This appointment can no longer be cancelled", ["reason" => "invalid_state", "currentStatus" => $appt['status']]);
}

$updateResult = pg_query_params($conn,
    "UPDATE appointment SET status = 'cancelled', needs_staff_ack = true WHERE appointment_id = $1",
    array($data['appointmentId'])
);
fail_on_db_error($updateResult, 'appointments/cancel_appointment: update', $conn);

send_success(["message" => "Appointment cancelled"]);

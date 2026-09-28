<?php
// appointments/acknowledge_appointment.php -- clinic staff dismisses a
// patient-initiated cancellation (or, later, reschedule) notice.
// This never changes the appointment's own status -- the patient's action
// already took effect automatically. This only clears the "staff hasn't
// seen this yet" flag so it stops surfacing under the Active filter.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'appointmentId' => ['required' => true, 'type' => 'int'],
]);

$conn = getConnection();

$updateResult = pg_query_params($conn,
    "UPDATE appointment SET needs_staff_ack = false, previous_date = NULL, previous_time = NULL WHERE appointment_id = $1 RETURNING appointment_id",
    array($data['appointmentId'])
);
fail_on_db_error($updateResult, 'appointments/acknowledge_appointment: update', $conn);

if (pg_num_rows($updateResult) === 0) {
    send_error(404, "Appointment not found");
}

send_success(["message" => "Acknowledged"]);

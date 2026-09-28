<?php
// appointments/get_appointment_details.php - just displays the details of a single appointment, for use in the app's "appointment details" screen. 

require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$id = require_query_param('id');

$conn = getConnection();

$result = pg_query_params($conn,
    "SELECT appointment_id, patient_id, dentist_id, service_id,
            appointment_date, appointment_time, status, notes, source
     FROM appointment WHERE appointment_id = $1",
    array($id)
);
fail_on_db_error($result, 'appointments/get_appointment_details', $conn);
$appointment = pg_fetch_assoc($result);

if (!$appointment) {
    send_error(404, "Appointment not found");
}

send_success(["appointment" => $appointment]);

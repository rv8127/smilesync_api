<?php
// appointments/get_appointments.php - clinic staff only, get all appointments for a given clinic code
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$clinicCode = require_query_param('clinicCode');

$conn = getConnection();

$result = pg_query_params($conn,
    "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.notes, a.source, a.needs_staff_ack,
            a.previous_date, a.previous_time,
            p.first_name AS patient_first_name, p.last_name AS patient_last_name,
            d.dentist_id, d.first_name AS dentist_first_name, d.last_name AS dentist_last_name,
            s.service_id, s.service_name
     FROM appointment a
     JOIN patient p ON p.patient_id = a.patient_id
     JOIN dentist d ON d.dentist_id = a.dentist_id
     JOIN service s ON s.service_id = a.service_id
     JOIN clinic c ON c.clinic_id = a.clinic_id
     WHERE c.clinic_code = $1
     ORDER BY a.appointment_date, a.appointment_time",
    array($clinicCode)
);
fail_on_db_error($result, 'appointments/get_appointments', $conn);

$appointments = pg_fetch_all($result) ?: [];
foreach ($appointments as &$appt) {
    $appt['needs_staff_ack'] = $appt['needs_staff_ack'] === 't';
}
unset($appt);

send_success(["appointments" => $appointments]);

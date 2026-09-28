<?php
// patients/get_patient_appointments.php - Endpoint to retrieve a patient's appointment history for a specific clinic  
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$patientId = require_query_param('patientId');
$clinicCode = require_query_param('clinicCode');

$conn = getConnection();
$clinic = require_clinic($conn, $clinicCode);

// Scoped to this clinic via a.clinic_id -- a patientId from another clinic
// simply returns no rows rather than that clinic's appointment history.
$result = pg_query_params($conn,
    "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.notes,
            d.first_name AS dentist_first_name, d.last_name AS dentist_last_name,
            s.service_name
     FROM appointment a
     JOIN dentist d ON d.dentist_id = a.dentist_id
     JOIN service s ON s.service_id = a.service_id
     WHERE a.patient_id = $1 AND a.clinic_id = $2
     ORDER BY a.appointment_date DESC, a.appointment_time DESC",
    array($patientId, $clinic['clinic_id'])
);
fail_on_db_error($result, 'patients/get_patient_appointments', $conn);

send_success(["appointments" => pg_fetch_all($result) ?: []]);

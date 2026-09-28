<?php
// patients/get_treatment_history.php - Endpoint to retrieve a patient's treatment history, scoped to a specific clinic
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$patientId = require_query_param('patientId');
$clinicCode = require_query_param('clinicCode');

$conn = getConnection();
$clinic = require_clinic($conn, $clinicCode);

// treatment_record has no clinic_id column of its own, so scope via a join
// to patient (which does) -- a patientId from another clinic returns no
// rows rather than that clinic's treatment history.
$result = pg_query_params($conn,
    "SELECT tr.treatment_record_id, tr.record_date, tr.notes,
            d.first_name AS dentist_first_name, d.last_name AS dentist_last_name,
            s.service_name
     FROM treatment_record tr
     JOIN patient p ON p.patient_id = tr.patient_id
     JOIN dentist d ON d.dentist_id = tr.dentist_id
     JOIN service s ON s.service_id = tr.service_id
     WHERE tr.patient_id = $1 AND p.clinic_id = $2
     ORDER BY tr.record_date DESC",
    array($patientId, $clinic['clinic_id'])
);
fail_on_db_error($result, 'patients/get_treatment_history', $conn);

send_success(["records" => pg_fetch_all($result) ?: []]);

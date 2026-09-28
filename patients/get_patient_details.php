<?php
// patients/get_patient_details.php - Endpoint to retrieve a patient's details for a specific clinic
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$id = require_query_param('id');
$clinicCode = require_query_param('clinicCode');

$conn = getConnection();
$clinic = require_clinic($conn, $clinicCode);

$result = pg_query_params($conn,
    "SELECT p.patient_id, p.first_name, p.last_name, p.contact_email, p.contact_number,
            p.address, p.birthdate, p.gender, p.created_at,
            CASE WHEN pa.patient_account_id IS NOT NULL THEN 'active' ELSE 'pending' END AS status
     FROM patient p
     LEFT JOIN patient_account pa ON pa.patient_id = p.patient_id
     WHERE p.patient_id = $1 AND p.clinic_id = $2",
    array($id, $clinic['clinic_id'])
);
fail_on_db_error($result, 'patients/get_patient_details', $conn);
$patient = pg_fetch_assoc($result);

// Deliberately the same 404/message whether the id doesn't exist at all or
// belongs to a different clinic -- doesn't confirm to a caller that a given
// id exists somewhere else on the platform.
if (!$patient) {
    send_error(404, "Patient not found");
}

send_success(["patient" => $patient]);

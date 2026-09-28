<?php
// patients/delete_patient.php
// If the patient has any appointment/treatment/account history, this now
// ARCHIVES them (is_archived = true) instead of refusing outright -- keeps
// history intact, removes them from the active roster and from being
// bookable for new appointments. Only a patient with zero history gets a
// real hard DELETE, same as before.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'clinicCode' => ['required' => true, 'type' => 'string'],
    'patientId'  => ['required' => true, 'type' => 'int'],
]);

$conn = getConnection();
$clinic = require_clinic($conn, $data['clinicCode']);

$existingResult = pg_query_params($conn,
    "SELECT patient_id FROM patient WHERE patient_id = $1 AND clinic_id = $2",
    array($data['patientId'], $clinic['clinic_id'])
);
fail_on_db_error($existingResult, 'patients/delete_patient: lookup', $conn);

// Same "not found" whether the id is missing or belongs to another clinic.
if (!pg_fetch_assoc($existingResult)) {
    send_error(404, "Patient not found");
}

$apptCheck = pg_query_params($conn,
    "SELECT COUNT(*) AS count FROM appointment WHERE patient_id = $1",
    array($data['patientId'])
);
fail_on_db_error($apptCheck, 'patients/delete_patient: appointment check', $conn);
$hasAppointments = (int) pg_fetch_assoc($apptCheck)['count'] > 0;

$treatmentCheck = pg_query_params($conn,
    "SELECT COUNT(*) AS count FROM treatment_record WHERE patient_id = $1",
    array($data['patientId'])
);
fail_on_db_error($treatmentCheck, 'patients/delete_patient: treatment check', $conn);
$hasTreatments = (int) pg_fetch_assoc($treatmentCheck)['count'] > 0;

$accountCheck = pg_query_params($conn,
    "SELECT COUNT(*) AS count FROM patient_account WHERE patient_id = $1",
    array($data['patientId'])
);
fail_on_db_error($accountCheck, 'patients/delete_patient: account check', $conn);
$hasAccount = (int) pg_fetch_assoc($accountCheck)['count'] > 0;

if ($hasAppointments || $hasTreatments || $hasAccount) {
    $archiveResult = pg_query_params($conn,
        "UPDATE patient SET is_archived = true WHERE patient_id = $1 AND clinic_id = $2",
        array($data['patientId'], $clinic['clinic_id'])
    );
    fail_on_db_error($archiveResult, 'patients/delete_patient: archive', $conn);

    send_success([
        "message" => "This patient has existing history, so they were archived instead of deleted. Their records are preserved and they're hidden from the active list.",
        "archived" => true
    ]);
}

$result = pg_query_params($conn,
    "DELETE FROM patient WHERE patient_id = $1 AND clinic_id = $2",
    array($data['patientId'], $clinic['clinic_id'])
);
fail_on_db_error($result, 'patients/delete_patient: delete', $conn);

send_success(["message" => "Patient deleted", "archived" => false]);

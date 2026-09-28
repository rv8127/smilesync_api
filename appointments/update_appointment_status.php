<?php
// appointments/update_appointment_status.php -- clinic staff only, status/notes-only edit of an existing appointment (no date/time/dentist/service changes)
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'appointmentId' => ['required' => true, 'type' => 'int'],
    'status'        => ['required' => true, 'type' => 'string'],
]);

$conn = getConnection();

$apptResult = pg_query_params($conn,
    "SELECT patient_id, dentist_id, service_id, appointment_date FROM appointment WHERE appointment_id = $1",
    array($data['appointmentId'])
);
fail_on_db_error($apptResult, 'appointments/update_appointment_status: lookup', $conn);
$appt = pg_fetch_assoc($apptResult);

if (!$appt) {
    send_error(404, "Appointment not found");
}

$updateResult = pg_query_params($conn,
    "UPDATE appointment SET status = $1 WHERE appointment_id = $2",
    array($data['status'], $data['appointmentId'])
);
fail_on_db_error($updateResult, 'appointments/update_appointment_status: update', $conn);

$treatmentCreated = false;

if ($data['status'] === 'completed') {
    $dupCheck = pg_query_params($conn,
        "SELECT treatment_record_id FROM treatment_record
         WHERE patient_id = $1 AND dentist_id = $2 AND service_id = $3 AND record_date = $4",
        array($appt['patient_id'], $appt['dentist_id'], $appt['service_id'], $appt['appointment_date'])
    );
    fail_on_db_error($dupCheck, 'appointments/update_appointment_status: treatment dup check', $conn);

    if (pg_num_rows($dupCheck) === 0) {
        $treatmentInsert = pg_query_params($conn,
            "INSERT INTO treatment_record (patient_id, dentist_id, service_id, record_date, notes)
             VALUES ($1, $2, $3, $4, $5)",
            array($appt['patient_id'], $appt['dentist_id'], $appt['service_id'], $appt['appointment_date'], 'Auto-logged from completed appointment.')
        );
        fail_on_db_error($treatmentInsert, 'appointments/update_appointment_status: treatment insert', $conn);
        $treatmentCreated = true;
    }
}

send_success([
    "message" => "Appointment marked as {$data['status']}",
    "treatmentRecordCreated" => $treatmentCreated
]);

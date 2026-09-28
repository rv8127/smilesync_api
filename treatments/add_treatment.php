<?php
// treatments/add_treatment.php - add a treatment record for a patient 
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'patientId' => ['required' => true, 'type' => 'int'],
    'dentistId' => ['required' => true, 'type' => 'int'],
    'serviceId' => ['required' => true, 'type' => 'int'],
    'date'      => ['required' => true, 'type' => 'date'],
    'notes'     => ['type' => 'string'],
]);

$conn = getConnection();

$insertResult = pg_query_params($conn,
    "INSERT INTO treatment_record (patient_id, dentist_id, service_id, record_date, notes)
     VALUES ($1, $2, $3, $4, $5) RETURNING treatment_record_id",
    array(
        $data['patientId'],
        $data['dentistId'],
        $data['serviceId'],
        $data['date'],
        $data['notes'] ?? null
    )
);
fail_on_db_error($insertResult, 'treatments/add_treatment: insert', $conn);

$newRecord = pg_fetch_assoc($insertResult);

send_success([
    "message" => "Treatment record saved successfully",
    "treatmentRecordId" => $newRecord['treatment_record_id']
]);

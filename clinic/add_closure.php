<?php
// clinic/add_closure.php - This implements actual closure in clinics, Patients/Clinic staff cant set appointments on closure dates.
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'clinicCode' => ['required' => true, 'type' => 'string'],
    'date'       => ['required' => true, 'type' => 'date'],
    'reason'     => ['type' => 'string'],
]);

$conn = getConnection();
$clinic = require_clinic($conn, $data['clinicCode']);

// One closure per clinic per date
$dupResult = pg_query_params($conn,
    "SELECT clinic_closure_id FROM clinic_closure WHERE clinic_id = $1 AND closure_date = $2",
    array($clinic['clinic_id'], $data['date'])
);
fail_on_db_error($dupResult, 'clinic/add_closure: duplicate check', $conn);

if (pg_num_rows($dupResult) > 0) {
    send_error(422, "A closure is already scheduled for this date", ["reason" => "duplicate"]);
}

$insertResult = pg_query_params($conn,
    "INSERT INTO clinic_closure (clinic_id, closure_date, reason)
     VALUES ($1, $2, $3) RETURNING clinic_closure_id",
    array($clinic['clinic_id'], $data['date'], $data['reason'] ?? null)
);
fail_on_db_error($insertResult, 'clinic/add_closure: insert', $conn);

$newClosure = pg_fetch_assoc($insertResult);

send_success([
    "message" => "Closure added successfully",
    "closureId" => $newClosure['clinic_closure_id']
]);

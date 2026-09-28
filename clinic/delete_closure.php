<?php
// clinic/delete_closure.php - deletes an closure date from closure table. 
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'closureId' => ['required' => true, 'type' => 'int'],
]);

$conn = getConnection();

$existingResult = pg_query_params($conn,
    "SELECT clinic_closure_id FROM clinic_closure WHERE clinic_closure_id = $1",
    array($data['closureId'])
);
fail_on_db_error($existingResult, 'clinic/delete_closure: lookup', $conn);

if (!pg_fetch_assoc($existingResult)) {
    send_error(404, "Closure not found");
}

$result = pg_query_params($conn,
    "DELETE FROM clinic_closure WHERE clinic_closure_id = $1",
    array($data['closureId'])
);
fail_on_db_error($result, 'clinic/delete_closure: delete', $conn);

send_success(["message" => "Closure deleted"]);

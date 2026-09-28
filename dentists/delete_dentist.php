<?php
// dentists/delete_dentist.php
// SOFT delete: sets status = 'inactive' rather than removing the row.
// A hard DELETE would fail on the FK from appointment.dentist_id / treatment_record.dentist_id
// once the dentist has any history -- soft delete also matches the Active/Inactive
// status pill already shown in dentists.html, so this is consistent with existing UI, not new behavior.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'dentistId' => ['required' => true, 'type' => 'int'],
]);

$conn = getConnection();

$existingResult = pg_query_params($conn,
    "SELECT dentist_id FROM dentist WHERE dentist_id = $1",
    array($data['dentistId'])
);
fail_on_db_error($existingResult, 'dentists/delete_dentist: lookup', $conn);

if (!pg_fetch_assoc($existingResult)) {
    send_error(404, "Dentist not found");
}

$result = pg_query_params($conn,
    "UPDATE dentist SET status = 'inactive' WHERE dentist_id = $1",
    array($data['dentistId'])
);
fail_on_db_error($result, 'dentists/delete_dentist: update', $conn);

send_success(["message" => "Dentist deactivated"]);

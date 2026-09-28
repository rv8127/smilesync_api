<?php
// dentists/delete_schedule_entry.php - deletes a schedule entry for a dentist in a clinic
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'scheduleId' => ['required' => true, 'type' => 'int'],
]);

$conn = getConnection();

$existingResult = pg_query_params($conn,
    "SELECT schedule_id FROM dentist_schedule WHERE schedule_id = $1",
    array($data['scheduleId'])
);
fail_on_db_error($existingResult, 'dentists/delete_schedule_entry: lookup', $conn);

if (!pg_fetch_assoc($existingResult)) {
    send_error(404, "Schedule entry not found");
}

$result = pg_query_params($conn,
    "DELETE FROM dentist_schedule WHERE schedule_id = $1",
    array($data['scheduleId'])
);
fail_on_db_error($result, 'dentists/delete_schedule_entry: delete', $conn);

send_success(["message" => "Schedule entry deleted"]);

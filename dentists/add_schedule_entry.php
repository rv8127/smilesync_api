<?php
// dentists/add_schedule_entry.php - adds a schedule entry for a dentist in a clinic
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'dentistId' => ['required' => true, 'type' => 'int'],
    'dayOfWeek' => ['required' => true, 'type' => 'string'],
    'startTime' => ['required' => true, 'type' => 'time'],
    'endTime'   => ['required' => true, 'type' => 'time'],
]);

$validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
if (!in_array($data['dayOfWeek'], $validDays, true)) {
    send_error(422, "Validation failed", ["dayOfWeek" => "must be one of: " . implode(', ', $validDays)]);
}

if (strtotime($data['endTime']) <= strtotime($data['startTime'])) {
    send_error(422, "Validation failed", ["endTime" => "must be later than the start time"]);
}

$conn = getConnection();

$dentistResult = pg_query_params($conn,
    "SELECT dentist_id FROM dentist WHERE dentist_id = $1",
    array($data['dentistId'])
);
fail_on_db_error($dentistResult, 'dentists/add_schedule_entry: dentist lookup', $conn);

if (!pg_fetch_assoc($dentistResult)) {
    send_error(404, "Dentist not found");
}

// One entry per dentist per day -- prevents conflicting/duplicate day rows
$dupResult = pg_query_params($conn,
    "SELECT schedule_id FROM dentist_schedule WHERE dentist_id = $1 AND day_of_week = $2",
    array($data['dentistId'], $data['dayOfWeek'])
);
fail_on_db_error($dupResult, 'dentists/add_schedule_entry: duplicate check', $conn);

if (pg_num_rows($dupResult) > 0) {
    send_error(422, "This dentist already has a schedule entry for {$data['dayOfWeek']}", ["reason" => "duplicate"]);
}

$insertResult = pg_query_params($conn,
    "INSERT INTO dentist_schedule (dentist_id, day_of_week, start_time, end_time)
     VALUES ($1, $2, $3, $4) RETURNING schedule_id",
    array($data['dentistId'], $data['dayOfWeek'], $data['startTime'], $data['endTime'])
);
fail_on_db_error($insertResult, 'dentists/add_schedule_entry: insert', $conn);

$newEntry = pg_fetch_assoc($insertResult);

send_success([
    "message" => "Schedule entry added successfully",
    "scheduleId" => $newEntry['schedule_id']
]);

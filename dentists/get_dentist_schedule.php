<?php
// dentists/get_dentist_schedule.php - retrieves the schedule for a specific dentist in a clinic
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$dentistId = require_query_param('dentistId');

$conn = getConnection();

// Confirm the dentist exists so a bad id gives a clear 404 instead of an empty list
$dentistResult = pg_query_params($conn,
    "SELECT dentist_id FROM dentist WHERE dentist_id = $1",
    array($dentistId)
);
fail_on_db_error($dentistResult, 'dentists/get_dentist_schedule: dentist lookup', $conn);

if (!pg_fetch_assoc($dentistResult)) {
    send_error(404, "Dentist not found");
}

$result = pg_query_params($conn,
    "SELECT schedule_id, day_of_week, start_time, end_time
     FROM dentist_schedule
     WHERE dentist_id = $1
     ORDER BY schedule_id",
    array($dentistId)
);
fail_on_db_error($result, 'dentists/get_dentist_schedule', $conn);

send_success(["schedule" => pg_fetch_all($result) ?: []]);

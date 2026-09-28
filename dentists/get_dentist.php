<?php
// dentists/get_dentist.php -- single dentist, by id - used by the clinic web admin to view dentist details
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$id = require_query_param('id');

$conn = getConnection();

$result = pg_query_params($conn,
    "SELECT dentist_id, first_name, last_name, specialization, status FROM dentist WHERE dentist_id = $1",
    array($id)
);
fail_on_db_error($result, 'dentists/get_dentist', $conn);
$dentist = pg_fetch_assoc($result);

if (!$dentist) {
    send_error(404, "Dentist not found");
}

send_success(["dentist" => $dentist]);

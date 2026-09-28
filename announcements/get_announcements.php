<?php
// announcements/get_announcements.php
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$clinicCode = require_query_param('clinicCode');

$conn = getConnection();

$result = pg_query_params($conn,
    "SELECT a.announcement_id, a.title, a.message, a.type, a.announcement_date
     FROM announcement a
     JOIN clinic c ON c.clinic_id = a.clinic_id
     WHERE c.clinic_code = $1
     ORDER BY a.announcement_date DESC",
    array($clinicCode)
);
fail_on_db_error($result, 'announcements/get_announcements', $conn);

send_success(["announcements" => pg_fetch_all($result) ?: []]);

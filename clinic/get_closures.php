<?php
// clinic/get_closures.php - Returns all closure dates for a clinic, so the frontend can disable appointment booking on those dates.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$clinicCode = require_query_param('clinicCode');

$conn = getConnection();

$result = pg_query_params($conn,
    "SELECT cc.clinic_closure_id, cc.closure_date, cc.reason
     FROM clinic_closure cc
     JOIN clinic c ON c.clinic_id = cc.clinic_id
     WHERE c.clinic_code = $1
     ORDER BY cc.closure_date DESC",
    array($clinicCode)
);
fail_on_db_error($result, 'clinic/get_closures', $conn);

send_success(["closures" => pg_fetch_all($result) ?: []]);

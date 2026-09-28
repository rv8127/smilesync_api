<?php
// patients/get_patients.php - Endpoint to retrieve a list of patients for a specific clinic, with optional filters
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

// clinicCode is REQUIRED here, unlike the optional filters on
// get_dentists.php / get_services.php -- patient records are more
// sensitive, and this endpoint previously had no clinic scoping at all
// (returned every patient from every clinic). Required + an explicit
// clinic lookup closes that off completely rather than leaving it as an
// easy-to-forget optional param.
$clinicCode = require_query_param('clinicCode');

$conn = getConnection();
$clinic = require_clinic($conn, $clinicCode);

$conditions = ["p.clinic_id = $1"];
$params = [$clinic['clinic_id']];

// &archived=false (the normal default view) shows only non-archived patients.
// &archived=true shows only archived ones. Omitting the param entirely
// returns everyone at this clinic, archived or not.
if (isset($_GET['archived'])) {
    $archived = filter_var($_GET['archived'], FILTER_VALIDATE_BOOLEAN);
    $conditions[] = "p.is_archived = $" . (count($params) + 1) . "::boolean";
    $params[] = $archived ? 'true' : 'false';
}

$where = "WHERE " . implode(" AND ", $conditions);

$sql = "SELECT p.patient_id, p.first_name, p.last_name, p.contact_email, p.contact_number, p.is_archived,
               CASE WHEN pa.patient_account_id IS NOT NULL THEN 'active' ELSE 'pending' END AS status
        FROM patient p
        LEFT JOIN patient_account pa ON pa.patient_id = p.patient_id
        $where
        ORDER BY p.patient_id";

$result = pg_query_params($conn, $sql, $params);
fail_on_db_error($result, 'patients/get_patients', $conn);

$patients = pg_fetch_all($result) ?: [];

// pg_fetch_all returns booleans as the raw strings 't'/'f', which json_encode
// serializes as JSON strings -- and in JS, both "t" and "f" are truthy, so
// every row would display as archived regardless of its real value.
// Converting to a real PHP boolean here makes it a real JSON true/false.
foreach ($patients as &$p) {
    $p['is_archived'] = ($p['is_archived'] === 't' || $p['is_archived'] === true);
}
unset($p);

send_success(["patients" => $patients]);

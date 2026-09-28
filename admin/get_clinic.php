<?php
// admin/get_clinic.php -- single clinic by numeric id (?id=), used to
// prefill clinic-form.html when editing an existing clinic.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/check_auth.php';

requireAdminAuth();
$clinicId = require_query_param('id');

$conn = getConnection();
$result = pg_query_params($conn,
    "SELECT clinic_id, clinic_name, clinic_code, address, contact_email, contact_number, status,
            opening_time, closing_time, tier
     FROM clinic WHERE clinic_id = $1",
    array($clinicId)
);
fail_on_db_error($result, 'admin/get_clinic', $conn);
$clinic = pg_fetch_assoc($result);

if (!$clinic) {
    send_error(404, "Clinic not found");
}

send_success(["clinic" => $clinic]);

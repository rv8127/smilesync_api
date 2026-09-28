<?php
// clinic/get_clinic_info.php - Gives clinic information 
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$clinicCode = require_query_param('clinicCode');

$conn = getConnection();
// require_clinic() now also enforces the clinic being active -- this makes
// get_clinic_info.php double as the lightweight "is my clinic still
// active?" check every staff page polls via clinic-lock.js, in addition to
// its original job of feeding clinic-info.html.
$clinic = require_clinic($conn, $clinicCode);

$result = pg_query_params($conn,
    "SELECT clinic_name, contact_email, contact_number, address, opening_time, closing_time
     FROM clinic WHERE clinic_id = $1",
    array($clinic['clinic_id'])
);
fail_on_db_error($result, 'clinic/get_clinic_info', $conn);
$info = pg_fetch_assoc($result);

send_success(["clinic" => $info]);

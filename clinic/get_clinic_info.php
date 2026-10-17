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

// Subscription dates are only sent when asked for (?include=subscription), which
// only clinic-info.html does. Every other caller of this endpoint (the page-lock
// check, the edit form, the patient app) gets the same fields as before, plus
// clinic_code and tier. days_left is counted in Philippine time and goes
// negative once the subscription has expired.
$includeSubscription = ($_GET['include'] ?? '') === 'subscription';
$subscriptionColumns = $includeSubscription
    ? ", subscription_start, subscription_end,
       (subscription_end - (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Manila')::date) AS days_left"
    : "";

$result = pg_query_params($conn,
    "SELECT clinic_name, clinic_code, tier, contact_email, contact_number, address, opening_time, closing_time
            $subscriptionColumns
     FROM clinic WHERE clinic_id = $1",
    array($clinic['clinic_id'])
);
fail_on_db_error($result, 'clinic/get_clinic_info', $conn);
$info = pg_fetch_assoc($result);

send_success(["clinic" => $info]);

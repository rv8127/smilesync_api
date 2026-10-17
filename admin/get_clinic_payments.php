<?php
// admin/get_clinic_payments.php -- payment history for one clinic (?id=), used by
// clinic-payments.html. Returns the clinic's subscription summary, every payment
// newest first, and the total paid so far.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/check_auth.php';

requireAdminAuth();
$clinicId = require_query_param('id');
if (!ctype_digit($clinicId)) {
    send_error(422, "Validation failed", ["id" => "must be a whole number"]);
}

$conn = getConnection();

$clinicResult = pg_query_params($conn,
    "SELECT clinic_id, clinic_name, clinic_code, contact_email, status, tier, subscription_start, subscription_end,
            (subscription_end - (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Manila')::date) AS days_left
     FROM clinic WHERE clinic_id = $1",
    array($clinicId)
);
fail_on_db_error($clinicResult, 'admin/get_clinic_payments: clinic lookup', $conn);
$clinic = pg_fetch_assoc($clinicResult);
if (!$clinic) {
    send_error(404, "Clinic not found");
}

$paymentsResult = pg_query_params($conn,
    "SELECT cp.payment_id, cp.amount, cp.paid_on, cp.period_start, cp.period_end, cp.method, cp.reference_no, cp.note,
            sa.login_email AS recorded_by
     FROM clinic_payment cp
     LEFT JOIN system_admin sa ON sa.admin_id = cp.recorded_by
     WHERE cp.clinic_id = $1
     ORDER BY cp.paid_on DESC, cp.payment_id DESC",
    array($clinicId)
);
fail_on_db_error($paymentsResult, 'admin/get_clinic_payments: payments', $conn);
$payments = pg_fetch_all($paymentsResult) ?: [];

$totalPaid = 0.0;
foreach ($payments as $p) {
    $totalPaid += (float) $p['amount'];
}

send_success([
    "clinic" => $clinic,
    "payments" => $payments,
    "totalPaid" => round($totalPaid, 2)
]);

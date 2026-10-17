<?php
// admin/record_payment.php -- the System Admin logs a clinic's subscription payment.
// Each payment buys 30 days:
//   - subscription still running (due date today or later): the new term starts on the
//     current due date, so paying early adds 30 days to it;
//   - expired, or no subscription yet: a new 30-day term starts today.
// Archived clinics are reactivated separately (update_clinic_status.php) once this is done.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/check_auth.php';
require_once '../helpers/log_activity.php';

$adminId = requireAdminAuth();
$data = get_json_body();

$data = validate_or_fail($data, [
    'id'        => ['required' => true, 'type' => 'int'],
    'amount'    => ['required' => true, 'type' => 'number'],
    'paidOn'    => ['type' => 'date'],
    'method'    => ['type' => 'string'],
    'reference' => ['type' => 'string'],
    'note'      => ['type' => 'string'],
]);

$amount = (float) $data['amount'];
if ($amount <= 0 || $amount > 99999999.99) {
    send_error(422, "Validation failed", ["amount" => "must be greater than 0"]);
}

$method = !empty($data['method']) ? $data['method'] : 'cash';
if (!in_array($method, ['cash', 'gcash', 'bank_transfer', 'other'])) {
    send_error(422, "Validation failed", ["method" => "must be cash, gcash, bank_transfer or other"]);
}

$reference = !empty($data['reference']) ? $data['reference'] : null;
if ($reference !== null && strlen($reference) > 100) {
    send_error(422, "Validation failed", ["reference" => "must be at most 100 characters"]);
}
$note = !empty($data['note']) ? $data['note'] : null;
if ($note !== null && strlen($note) > 500) {
    send_error(422, "Validation failed", ["note" => "must be at most 500 characters"]);
}

// "Today" is the Philippine date, same as the days_left counts everywhere else.
$today = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');
$paidOn = !empty($data['paidOn']) ? $data['paidOn'] : $today;
if ($paidOn > $today) {
    send_error(422, "Validation failed", ["paidOn" => "can't be in the future"]);
}

$conn = getConnection();

// Lock the clinic row so two payments recorded at the same moment can't both extend from the same due date.
// If anything below stops the script, the open transaction is rolled back when the connection closes.
pg_query($conn, "BEGIN");

$clinicResult = pg_query_params($conn,
    "SELECT clinic_name, subscription_end FROM clinic WHERE clinic_id = $1 FOR UPDATE",
    array($data['id'])
);
fail_on_db_error($clinicResult, 'admin/record_payment: clinic lookup', $conn);
$clinic = pg_fetch_assoc($clinicResult);
if (!$clinic) {
    send_error(404, "Clinic not found");
}

$currentEnd = $clinic['subscription_end'];            // 'YYYY-MM-DD' or null
$advance = $currentEnd !== null && $currentEnd >= $today;
$periodStart = $advance ? $currentEnd : $today;
$periodEnd = (new DateTime($periodStart))->modify('+30 days')->format('Y-m-d');

$paymentResult = pg_query_params($conn,
    "INSERT INTO clinic_payment (clinic_id, amount, paid_on, period_start, period_end, method, reference_no, note, recorded_by)
     VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9) RETURNING payment_id",
    array($data['id'], $amount, $paidOn, $periodStart, $periodEnd, $method, $reference, $note, $adminId)
);
fail_on_db_error($paymentResult, 'admin/record_payment: insert payment', $conn);
$payment = pg_fetch_assoc($paymentResult);

if ($advance) {
    // Still running: keep the term's start date, just push the due date out.
    $updateResult = pg_query_params($conn,
        "UPDATE clinic SET subscription_start = COALESCE(subscription_start, $1), subscription_end = $2 WHERE clinic_id = $3",
        array($periodStart, $periodEnd, $data['id'])
    );
} else {
    // Expired / first payment: a fresh 30-day term starting today.
    $updateResult = pg_query_params($conn,
        "UPDATE clinic SET subscription_start = $1, subscription_end = $2 WHERE clinic_id = $3",
        array($periodStart, $periodEnd, $data['id'])
    );
}
fail_on_db_error($updateResult, 'admin/record_payment: extend subscription', $conn);

pg_query($conn, "COMMIT");

log_activity($conn, $adminId, $_SESSION['admin_email'] ?? null, 'clinic_payment_recorded', 'clinic', (int) $data['id'],
    "Payment of PHP " . number_format($amount, 2) . " recorded for {$clinic['clinic_name']} (due " . $periodEnd . ")");

send_success([
    "message" => "Payment recorded",
    "paymentId" => (int) $payment['payment_id'],
    "subscriptionStart" => $periodStart,
    "subscriptionEnd" => $periodEnd
], 201);

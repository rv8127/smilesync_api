<?php
// admin/log_renewal_reminder.php -- called by the System Admin pages right
// after a renewal-reminder email goes out (the email itself is sent from the
// browser through EmailJS, same as the password-reset email). This only
// records it in the activity log so there's a trail of who was reminded.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/check_auth.php';
require_once '../helpers/log_activity.php';

$adminId = requireAdminAuth();
$data = get_json_body();

$data = validate_or_fail($data, [
    'id' => ['required' => true, 'type' => 'int'],
]);

$conn = getConnection();

$clinicResult = pg_query_params($conn, "SELECT clinic_name FROM clinic WHERE clinic_id = $1", array($data['id']));
fail_on_db_error($clinicResult, 'admin/log_renewal_reminder: lookup', $conn);
$clinic = pg_fetch_assoc($clinicResult);
if (!$clinic) {
    send_error(404, "Clinic not found");
}

// Remember which due date this reminder was for, so the automatic "almost due"
// email isn't sent again for the same subscription term.
$markResult = pg_query_params($conn, "UPDATE clinic SET reminder_sent_for = subscription_end WHERE clinic_id = $1", array($data['id']));
fail_on_db_error($markResult, 'admin/log_renewal_reminder: mark sent', $conn);

$auto = !empty($data['auto']);
log_activity($conn, $adminId, $_SESSION['admin_email'] ?? null, 'renewal_reminder_sent', 'clinic', (int) $data['id'],
    ($auto ? "Automatic renewal reminder" : "Renewal reminder") . " emailed to {$clinic['clinic_name']}");

send_success(["message" => "Reminder logged"]);

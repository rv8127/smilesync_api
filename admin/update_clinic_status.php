<?php
// admin/update_clinic_status.php -- powers the Activate/Deactivate buttons
// on clinics.html. Kept separate from update_clinic.php so this specific
// action gets its own clean activity-log line.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/check_auth.php';
require_once '../helpers/log_activity.php';

$adminId = requireAdminAuth();
$data = get_json_body();

$data = validate_or_fail($data, [
    'id'     => ['required' => true, 'type' => 'int'],
    'status' => ['required' => true, 'type' => 'string'],
]);

if (!in_array($data['status'], ['active', 'inactive'])) {
    send_error(422, "Validation failed", ["status" => "must be 'active' or 'inactive'"]);
}

$conn = getConnection();

$clinicResult = pg_query_params($conn, "SELECT clinic_name FROM clinic WHERE clinic_id = $1", array($data['id']));
fail_on_db_error($clinicResult, 'admin/update_clinic_status: lookup', $conn);
$clinic = pg_fetch_assoc($clinicResult);
if (!$clinic) {
    send_error(404, "Clinic not found");
}

$updateResult = pg_query_params($conn, "UPDATE clinic SET status = $1 WHERE clinic_id = $2",
    array($data['status'], $data['id']));
fail_on_db_error($updateResult, 'admin/update_clinic_status: update', $conn);

$activated = $data['status'] === 'active';
log_activity(
    $conn, $adminId, $_SESSION['admin_email'] ?? null,
    $activated ? 'clinic_activated' : 'clinic_deactivated',
    'clinic', (int) $data['id'],
    "{$clinic['clinic_name']} was " . ($activated ? 'activated' : 'deactivated')
);

send_success(["message" => "Clinic status updated"]);

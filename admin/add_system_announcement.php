<?php
// admin/add_system_announcement.php -- System Admin posts a SmileSync-wide announcement
// (maintenance notice, app update, etc.). Each one is aimed at either the patient app
// or the clinic web admin, or both (audience), so the right people see it in the right place.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/check_auth.php';
require_once '../helpers/log_activity.php';

$adminId = requireAdminAuth();
$data = get_json_body();

$data = validate_or_fail($data, [
    'title'     => ['required' => true, 'type' => 'string'],
    'message'   => ['type' => 'string'],
    'type'      => ['type' => 'string'],
    'date'      => ['type' => 'date'],
    'expiresOn' => ['type' => 'date'],   // optional: stops showing after this date
    'audience'  => ['type' => 'string'], // 'patient_app' (default), 'web_admin' or 'both'
]);

$audience = $data['audience'] ?? 'patient_app';
if (!in_array($audience, ['patient_app', 'web_admin', 'both'])) {
    send_error(422, "Validation failed", ["audience" => "must be 'patient_app', 'web_admin' or 'both'"]);
}

$conn = getConnection();

$insertResult = pg_query_params($conn,
    "INSERT INTO system_announcement (title, message, type, announcement_date, expires_on, audience)
     VALUES ($1, $2, $3, COALESCE($4::date, CURRENT_DATE), $5::date, $6) RETURNING system_announcement_id",
    array(
        $data['title'],
        $data['message'] ?? null,
        $data['type'] ?? 'info',
        $data['date'] ?? null,
        $data['expiresOn'] ?? null,
        $audience
    )
);
fail_on_db_error($insertResult, 'admin/add_system_announcement: insert', $conn);
$newAnn = pg_fetch_assoc($insertResult);

log_activity(
    $conn, $adminId, $_SESSION['admin_email'] ?? null,
    'system_announcement_posted', 'system_announcement', (int) $newAnn['system_announcement_id'],
    "System announcement posted: {$data['title']}"
);

send_success([
    "message" => "System announcement posted successfully",
    "announcementId" => $newAnn['system_announcement_id']
]);

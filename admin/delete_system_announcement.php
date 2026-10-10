<?php
// admin/delete_system_announcement.php -- System Admin removes a SmileSync-wide announcement.
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

$lookup = pg_query_params($conn, "SELECT title FROM system_announcement WHERE system_announcement_id = $1", array($data['id']));
fail_on_db_error($lookup, 'admin/delete_system_announcement: lookup', $conn);
$ann = pg_fetch_assoc($lookup);
if (!$ann) {
    send_error(404, "Announcement not found");
}

$result = pg_query_params($conn, "DELETE FROM system_announcement WHERE system_announcement_id = $1", array($data['id']));
fail_on_db_error($result, 'admin/delete_system_announcement: delete', $conn);

log_activity(
    $conn, $adminId, $_SESSION['admin_email'] ?? null,
    'system_announcement_deleted', 'system_announcement', (int) $data['id'],
    "System announcement deleted: {$ann['title']}"
);

send_success(["message" => "Announcement deleted"]);

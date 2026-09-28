<?php
// admin/get_activity.php -- powers activity.html. Optional ?type= filters
// to 'clinic' or 'system_user' target rows, ?date= (YYYY-MM-DD) filters to
// that calendar day. Capped at 200 most-recent rows.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/check_auth.php';

requireAdminAuth();
$conn = getConnection();

$conditions = [];
$params = [];

if (!empty($_GET['type'])) {
    $conditions[] = "target_type = $" . (count($params) + 1);
    $params[] = $_GET['type'];
}
if (!empty($_GET['date'])) {
    $conditions[] = "created_at::date = $" . (count($params) + 1);
    $params[] = $_GET['date'];
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$sql = "SELECT log_id, action, actor_email, target_type, target_id, description, created_at
        FROM activity_log
        $where
        ORDER BY created_at DESC
        LIMIT 200";

$result = $params ? pg_query_params($conn, $sql, $params) : pg_query($conn, $sql);
fail_on_db_error($result, 'admin/get_activity', $conn);

send_success(["activity" => pg_fetch_all($result) ?: []]);

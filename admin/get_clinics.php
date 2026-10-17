<?php
// admin/get_clinics.php -- full clinic list for clinics.html, with patient
// and appointment counts, and optional ?search= / ?status= filters.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/check_auth.php';

requireAdminAuth();
$conn = getConnection();

$conditions = [];
$params = [];

if (!empty($_GET['search'])) {
    $idx = count($params) + 1;
    $conditions[] = "(c.clinic_name ILIKE \$$idx OR c.clinic_code ILIKE \$$idx)";
    $params[] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['status'])) {
    $conditions[] = "c.status = $" . (count($params) + 1);
    $params[] = $_GET['status'];
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Days are counted in Philippine time so the countdown flips at local midnight,
// not at 8 AM (the Render database runs in UTC).
$sql = "SELECT c.clinic_id, c.clinic_name, c.clinic_code, c.address, c.status, c.tier,
               c.contact_email, c.subscription_start, c.subscription_end,
               (c.subscription_end - (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Manila')::date) AS days_left,
               COUNT(DISTINCT p.patient_id) AS patient_count,
               COUNT(DISTINCT a.appointment_id) AS appointment_count
        FROM clinic c
        LEFT JOIN patient p ON p.clinic_id = c.clinic_id
        LEFT JOIN appointment a ON a.clinic_id = c.clinic_id
        $where
        GROUP BY c.clinic_id, c.clinic_name, c.clinic_code, c.address, c.status, c.tier,
                 c.contact_email, c.subscription_start, c.subscription_end
        ORDER BY c.clinic_id";

$result = $params ? pg_query_params($conn, $sql, $params) : pg_query($conn, $sql);
fail_on_db_error($result, 'admin/get_clinics', $conn);

send_success(["clinics" => pg_fetch_all($result) ?: []]);

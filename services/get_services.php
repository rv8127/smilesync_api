<?php
// services/get_services.php - service retrieval endpoint for clinic staff (e.g., Root Canal) -- requires clinic-level auth
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$conn = getConnection();

$conditions = [];
$params = [];

if (!empty($_GET['clinicCode'])) {
    $conditions[] = "c.clinic_code = $" . (count($params) + 1);
    $params[] = $_GET['clinicCode'];
}
if (!empty($_GET['status'])) {
    $conditions[] = "s.status = $" . (count($params) + 1);
    $params[] = $_GET['status'];
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$sql = "SELECT s.service_id, s.service_name, s.description, s.duration_minutes, s.price, s.status
        FROM service s
        JOIN clinic c ON c.clinic_id = s.clinic_id
        $where
        ORDER BY s.service_id";

$result = $params ? pg_query_params($conn, $sql, $params) : pg_query($conn, $sql);
fail_on_db_error($result, 'services/get_services', $conn);

send_success(["services" => pg_fetch_all($result) ?: []]);

<?php
// dentists/get_dentists.php - retrieves a list of dentists for a clinic, optionally filtered by status
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
    $conditions[] = "d.status = $" . (count($params) + 1);
    $params[] = $_GET['status'];
}

$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$sql = "SELECT d.dentist_id, d.first_name, d.last_name, d.specialization, d.status
        FROM dentist d
        JOIN clinic c ON c.clinic_id = d.clinic_id
        $where
        ORDER BY d.dentist_id";

$result = $params ? pg_query_params($conn, $sql, $params) : pg_query($conn, $sql);
fail_on_db_error($result, 'dentists/get_dentists', $conn);

send_success(["dentists" => pg_fetch_all($result) ?: []]);

<?php
// services/get_service.php -- single service, by id
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$id = require_query_param('id');

$conn = getConnection();

$result = pg_query_params($conn,
    "SELECT service_id, service_name, description, duration_minutes, price, status FROM service WHERE service_id = $1",
    array($id)
);
fail_on_db_error($result, 'services/get_service', $conn);
$service = pg_fetch_assoc($result);

if (!$service) {
    send_error(404, "Service not found");
}

send_success(["service" => $service]);

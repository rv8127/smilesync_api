<?php
// services/update_service.php - service update endpoint for clinic staff (e.g., Root Canal) -- requires clinic-level auth
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'serviceId'   => ['required' => true, 'type' => 'int'],
    'serviceName' => ['type' => 'string'],
    'description' => ['type' => 'string'],
    'duration'    => ['type' => 'number'],
    'price'       => ['type' => 'number'],
    'status'      => ['type' => 'string'],
]);

$conn = getConnection();

$existingResult = pg_query_params($conn,
    "SELECT service_id, service_name, description, duration_minutes, price, status FROM service WHERE service_id = $1",
    array($data['serviceId'])
);
fail_on_db_error($existingResult, 'services/update_service: lookup', $conn);
$existing = pg_fetch_assoc($existingResult);

if (!$existing) {
    send_error(404, "Service not found");
}

$serviceName = $data['serviceName'] ?? $existing['service_name'];
$description = $data['description'] ?? $existing['description'];
$duration    = $data['duration']    ?? $existing['duration_minutes'];
$price       = $data['price']       ?? $existing['price'];
$status      = $data['status']      ?? $existing['status'];

$updateResult = pg_query_params($conn,
    "UPDATE service SET service_name = $1, description = $2, duration_minutes = $3, price = $4, status = $5
     WHERE service_id = $6",
    array($serviceName, $description, $duration, $price, $status, $data['serviceId'])
);
fail_on_db_error($updateResult, 'services/update_service: update', $conn);

send_success(["message" => "Service updated successfully"]);

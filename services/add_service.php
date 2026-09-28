<?php
// services/add_service.php - service creation endpoint for clinic staff (e.g., Root Canal) -- requires clinic-level auth
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'clinicCode'  => ['required' => true, 'type' => 'string'],
    'serviceName' => ['required' => true, 'type' => 'string'],
    'description' => ['type' => 'string'],
    'duration'    => ['required' => true, 'type' => 'number'],
    'price'       => ['required' => true, 'type' => 'number'],
    'status'      => ['type' => 'string'],
]);

$conn = getConnection();
$clinic = require_clinic($conn, $data['clinicCode']);

$insertResult = pg_query_params($conn,
    "INSERT INTO service (clinic_id, service_name, description, duration_minutes, price, status)
     VALUES ($1, $2, $3, $4, $5, $6) RETURNING service_id",
    array(
        $clinic['clinic_id'],
        $data['serviceName'],
        $data['description'] ?? null,
        $data['duration'],
        $data['price'],
        $data['status'] ?? 'active'
    )
);
fail_on_db_error($insertResult, 'services/add_service: insert', $conn);

$newService = pg_fetch_assoc($insertResult);

send_success([
    "message" => "Service added successfully",
    "serviceId" => $newService['service_id']
]);

<?php
// services/delete_service.php
// SOFT delete: sets status = 'inactive', same reasoning as dentists/delete_dentist.php.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'serviceId' => ['required' => true, 'type' => 'int'],
]);

$conn = getConnection();

$existingResult = pg_query_params($conn,
    "SELECT service_id FROM service WHERE service_id = $1",
    array($data['serviceId'])
);
fail_on_db_error($existingResult, 'services/delete_service: lookup', $conn);

if (!pg_fetch_assoc($existingResult)) {
    send_error(404, "Service not found");
}

$result = pg_query_params($conn,
    "UPDATE service SET status = 'inactive' WHERE service_id = $1",
    array($data['serviceId'])
);
fail_on_db_error($result, 'services/delete_service: update', $conn);

send_success(["message" => "Service deactivated"]);

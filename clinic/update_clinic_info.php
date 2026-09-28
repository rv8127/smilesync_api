<?php
// clinic/update_clinic_info.php - Updates clinic information such as name, contact email, contact number, address, opening time, and closing time. 
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'clinicCode'    => ['required' => true, 'type' => 'string'],
    'clinicName'    => ['type' => 'string'],
    'contactEmail'  => ['type' => 'string'],
    'contactNumber' => ['type' => 'string'],
    'address'       => ['type' => 'string'],
    'openingTime'   => ['type' => 'time'],
    'closingTime'   => ['type' => 'time'],
]);

$conn = getConnection();
$clinic = require_clinic($conn, $data['clinicCode']);

$existingResult = pg_query_params($conn,
    "SELECT clinic_name, contact_email, contact_number, address, opening_time, closing_time
     FROM clinic WHERE clinic_id = $1",
    array($clinic['clinic_id'])
);
fail_on_db_error($existingResult, 'clinic/update_clinic_info: lookup', $conn);
$existing = pg_fetch_assoc($existingResult);

$clinicName    = $data['clinicName']    ?? $existing['clinic_name'];
$contactEmail  = $data['contactEmail']  ?? $existing['contact_email'];
$contactNumber = $data['contactNumber'] ?? $existing['contact_number'];
$address       = $data['address']       ?? $existing['address'];
$openingTime   = $data['openingTime']   ?? $existing['opening_time'];
$closingTime   = $data['closingTime']   ?? $existing['closing_time'];

$updateResult = pg_query_params($conn,
    "UPDATE clinic SET clinic_name = $1, contact_email = $2, contact_number = $3,
                       address = $4, opening_time = $5, closing_time = $6
     WHERE clinic_id = $7",
    array($clinicName, $contactEmail, $contactNumber, $address, $openingTime, $closingTime, $clinic['clinic_id'])
);
fail_on_db_error($updateResult, 'clinic/update_clinic_info: update', $conn);

send_success(["message" => "Clinic info updated successfully"]);

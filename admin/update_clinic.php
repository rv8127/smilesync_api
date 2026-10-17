<?php
// admin/update_clinic.php -- edits clinic details. Status changes go
// through update_clinic_status.php instead, so activate/deactivate gets its
// own clear activity-log entry rather than being buried in a generic edit.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/check_auth.php';

requireAdminAuth();
$data = get_json_body();

$data = validate_or_fail($data, [
    'id'          => ['required' => true, 'type' => 'int'],
    'name'        => ['type' => 'string'],
    'code'        => ['type' => 'string'],
    'email'       => ['type' => 'email'],
    'phone'       => ['type' => 'string'],
    'address'     => ['type' => 'string'],
    'openingTime' => ['type' => 'time'],
    'closingTime' => ['type' => 'time'],
    'tier'        => ['type' => 'string'],
    'subscriptionStart' => ['type' => 'date'],
    'subscriptionEnd'   => ['type' => 'date'],
]);

if (isset($data['tier']) && !in_array($data['tier'], ['basic', 'pro'])) {
    send_error(422, "Validation failed", ["tier" => "must be 'basic' or 'pro'"]);
}

$conn = getConnection();

$existingResult = pg_query_params($conn,
    "SELECT clinic_name, clinic_code, address, contact_email, contact_number, opening_time, closing_time, tier, subscription_start, subscription_end
     FROM clinic WHERE clinic_id = $1",
    array($data['id'])
);
fail_on_db_error($existingResult, 'admin/update_clinic: lookup', $conn);
$existing = pg_fetch_assoc($existingResult);
if (!$existing) {
    send_error(404, "Clinic not found");
}

if (!empty($data['code']) && $data['code'] !== $existing['clinic_code']) {
    $dupResult = pg_query_params($conn,
        "SELECT clinic_id FROM clinic WHERE clinic_code = $1 AND clinic_id != $2",
        array($data['code'], $data['id'])
    );
    fail_on_db_error($dupResult, 'admin/update_clinic: duplicate check', $conn);
    if (pg_num_rows($dupResult) > 0) {
        send_error(422, "A clinic with this code already exists", ["code" => "already in use"]);
    }
}

// Same rule as add_clinic.php: no two clinics can share an email (case-insensitive).
if (!empty($data['email']) && strtolower($data['email']) !== strtolower((string) $existing['contact_email'])) {
    $emailDup = pg_query_params($conn,
        "SELECT clinic_id FROM clinic WHERE LOWER(contact_email) = LOWER($1) AND clinic_id != $2",
        array($data['email'], $data['id'])
    );
    fail_on_db_error($emailDup, 'admin/update_clinic: email duplicate check', $conn);
    if (pg_num_rows($emailDup) > 0) {
        send_error(422, "A clinic with this email already exists", ["email" => "already in use"]);
    }
}

$name        = $data['name']        ?? $existing['clinic_name'];
$code        = $data['code']        ?? $existing['clinic_code'];
$address     = $data['address']     ?? $existing['address'];
$email       = $data['email']       ?? $existing['contact_email'];
$phone       = $data['phone']       ?? $existing['contact_number'];
$openingTime = $data['openingTime'] ?? $existing['opening_time'];
$closingTime = $data['closingTime'] ?? $existing['closing_time'];
$tier        = $data['tier']        ?? $existing['tier'];
$subStart    = !empty($data['subscriptionStart']) ? $data['subscriptionStart'] : $existing['subscription_start'];

// Subscriptions are monthly: whenever the start date is set or changed (a new
// subscription or a renewal), the due date is recalculated as start + 30 days.
// If the start date wasn't touched, the existing due date is left as it is.
$startChanged = !empty($data['subscriptionStart']) && $data['subscriptionStart'] !== $existing['subscription_start'];
if ($subStart && ($startChanged || empty($existing['subscription_end']))) {
    $subEnd = (new DateTime($subStart))->modify('+30 days')->format('Y-m-d');
} else {
    $subEnd = $existing['subscription_end'];
}

$updateResult = pg_query_params($conn,
    "UPDATE clinic SET clinic_name = $1, clinic_code = $2, address = $3, contact_email = $4, contact_number = $5,
                       opening_time = $6, closing_time = $7, tier = $8,
                       subscription_start = $9, subscription_end = $10
     WHERE clinic_id = $11",
    array($name, $code, $address, $email, $phone, $openingTime, $closingTime, $tier, $subStart, $subEnd, $data['id'])
);
fail_on_db_error($updateResult, 'admin/update_clinic: update', $conn);

send_success(["message" => "Clinic updated successfully"]);

<?php
// admin/add_clinic.php -- registers a new clinic. clinic.admin_id is
// mandatory per the ERD (every clinic must have a registering
// SYSTEM_ADMIN), so it's set from the signed-in admin's session, not a
// form field.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/check_auth.php';
require_once '../helpers/log_activity.php';

$adminId = requireAdminAuth();
$data = get_json_body();

$data = validate_or_fail($data, [
    'name'        => ['required' => true, 'type' => 'string'],
    'code'        => ['required' => true, 'type' => 'string'],
    'status'      => ['type' => 'string'],
    'email'       => ['type' => 'email'],
    'phone'       => ['type' => 'string'],
    'address'     => ['type' => 'string'],
    'openingTime' => ['type' => 'time'],
    'closingTime' => ['type' => 'time'],
    'tier'        => ['type' => 'string'],
    'subscriptionStart' => ['type' => 'date'],
    'subscriptionEnd'   => ['type' => 'date'],
]);

$status = $data['status'] ?? 'active';
if (!in_array($status, ['active', 'inactive'])) {
    send_error(422, "Validation failed", ["status" => "must be 'active' or 'inactive'"]);
}

$tier = $data['tier'] ?? 'basic';
if (!in_array($tier, ['basic', 'pro'])) {
    send_error(422, "Validation failed", ["tier" => "must be 'basic' or 'pro'"]);
}

// Subscriptions are monthly: the due date is never typed in, it's always the
// start date + 30 days, worked out here so it can't drift from the start date.
$subStart = !empty($data['subscriptionStart']) ? $data['subscriptionStart'] : null;
$subEnd   = $subStart ? (new DateTime($subStart))->modify('+30 days')->format('Y-m-d') : null;

$conn = getConnection();

$dupResult = pg_query_params($conn, "SELECT clinic_id FROM clinic WHERE clinic_code = $1", array($data['code']));
fail_on_db_error($dupResult, 'admin/add_clinic: duplicate check', $conn);
if (pg_num_rows($dupResult) > 0) {
    send_error(422, "A clinic with this code already exists", ["code" => "already in use"]);
}

// Each clinic needs its own email (it receives that clinic's welcome/reminder emails), so the same
// address can't be used for two clinics. Compared case-insensitively; blank emails are skipped.
if (!empty($data['email'])) {
    $emailDup = pg_query_params($conn, "SELECT clinic_id FROM clinic WHERE LOWER(contact_email) = LOWER($1)", array($data['email']));
    fail_on_db_error($emailDup, 'admin/add_clinic: email duplicate check', $conn);
    if (pg_num_rows($emailDup) > 0) {
        send_error(422, "A clinic with this email already exists", ["email" => "already in use"]);
    }
}

$insertResult = pg_query_params($conn,
    "INSERT INTO clinic (clinic_name, clinic_code, address, contact_email, contact_number, status, admin_id, opening_time, closing_time, tier, subscription_start, subscription_end)
     VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12) RETURNING clinic_id",
    array(
        $data['name'],
        $data['code'],
        $data['address'] ?? null,
        $data['email'] ?? null,
        $data['phone'] ?? null,
        $status,
        $adminId,
        $data['openingTime'] ?? null,
        $data['closingTime'] ?? null,
        $tier,
        $subStart,
        $subEnd
    )
);
fail_on_db_error($insertResult, 'admin/add_clinic: insert', $conn);
$newClinic = pg_fetch_assoc($insertResult);

log_activity($conn, $adminId, $_SESSION['admin_email'] ?? null, 'clinic_registered', 'clinic', (int) $newClinic['clinic_id'],
    "{$data['name']} was registered");

send_success([
    "message" => "Clinic registered successfully",
    "clinicId" => (int) $newClinic['clinic_id']
], 201);

<?php
// admin/update_user.php -- edits an existing system_admin row.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/check_auth.php';
require_once '../helpers/log_activity.php';

$adminId = requireAdminAuth();
$data = get_json_body();

$data = validate_or_fail($data, [
    'id'          => ['required' => true, 'type' => 'int'],
    'firstName'   => ['type' => 'string'],
    'lastName'    => ['type' => 'string'],
    'email'       => ['type' => 'email'],
    'role'        => ['type' => 'string'],
    'status'      => ['type' => 'string'],
    'newPassword' => ['type' => 'string', 'minlen' => 8],
]);

$conn = getConnection();

$existingResult = pg_query_params($conn,
    "SELECT first_name, last_name, login_email, role, account_status FROM system_admin WHERE admin_id = $1",
    array($data['id'])
);
fail_on_db_error($existingResult, 'admin/update_user: lookup', $conn);
$existing = pg_fetch_assoc($existingResult);
if (!$existing) {
    send_error(404, "System user not found");
}

if (!empty($data['role']) && !in_array($data['role'], ['system_admin', 'support_admin'])) {
    send_error(422, "Validation failed", ["role" => "must be 'system_admin' or 'support_admin'"]);
}
if (!empty($data['status']) && !in_array($data['status'], ['active', 'inactive'])) {
    send_error(422, "Validation failed", ["status" => "must be 'active' or 'inactive'"]);
}

if (!empty($data['email']) && $data['email'] !== $existing['login_email']) {
    $dupResult = pg_query_params($conn,
        "SELECT admin_id FROM system_admin WHERE login_email = $1 AND admin_id != $2",
        array($data['email'], $data['id'])
    );
    fail_on_db_error($dupResult, 'admin/update_user: duplicate check', $conn);
    if (pg_num_rows($dupResult) > 0) {
        send_error(422, "A system user with this email already exists", ["email" => "already in use"]);
    }
}

$firstName = $data['firstName'] ?? $existing['first_name'];
$lastName  = $data['lastName']  ?? $existing['last_name'];
$email     = $data['email']     ?? $existing['login_email'];
$role      = $data['role']      ?? $existing['role'];
$status    = $data['status']    ?? $existing['account_status'];

if (!empty($data['newPassword'])) {
    $newHash = password_hash($data['newPassword'], PASSWORD_DEFAULT);
    $updateResult = pg_query_params($conn,
        "UPDATE system_admin SET first_name = $1, last_name = $2, login_email = $3, role = $4, account_status = $5, password_hash = $6
         WHERE admin_id = $7",
        array($firstName, $lastName, $email, $role, $status, $newHash, $data['id'])
    );
} else {
    $updateResult = pg_query_params($conn,
        "UPDATE system_admin SET first_name = $1, last_name = $2, login_email = $3, role = $4, account_status = $5
         WHERE admin_id = $6",
        array($firstName, $lastName, $email, $role, $status, $data['id'])
    );
}
fail_on_db_error($updateResult, 'admin/update_user: update', $conn);

// whoami.php (and everywhere it feeds -- the sidebar, Settings) reads the
// admin's name/email from the SESSION, not the DB, so if the admin just
// edited their own account, refresh the session copy too -- otherwise the
// UI keeps showing the stale pre-edit name/email until the next login.
if ($data['id'] === $adminId) {
    $_SESSION['admin_name'] = "{$firstName} {$lastName}";
    $_SESSION['admin_email'] = $email;
}

log_activity($conn, $adminId, $_SESSION['admin_email'] ?? null, 'system_user_updated', 'system_user', (int) $data['id'],
    "{$firstName} {$lastName}'s account was updated");

send_success(["message" => "System user updated successfully"]);
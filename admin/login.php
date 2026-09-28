<?php
// admin/login.php -- SmileSync system admin login (platform-level, not clinic staff)
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/log_activity.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'email'    => ['required' => true, 'type' => 'string'],
    'password' => ['required' => true, 'type' => 'string'],
]);

$conn = getConnection();

$result = pg_query_params($conn,
    "SELECT admin_id, first_name, last_name, login_email, password_hash, account_status
     FROM system_admin
     WHERE login_email = $1",
    array($data['email'])
);
fail_on_db_error($result, 'admin/login: lookup', $conn);
$admin = pg_fetch_assoc($result);

if (!$admin || !password_verify($data['password'], $admin['password_hash'])) {
    send_error(401, "Invalid email or password");
}

if ($admin['account_status'] !== 'active') {
    send_error(401, "This account is not active", ["reason" => "inactive_account"]);
}

session_start();
$_SESSION['admin_id'] = $admin['admin_id'];
$_SESSION['admin_email'] = $admin['login_email'];
$_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];

$touchResult = pg_query_params($conn,
    "UPDATE system_admin SET last_login_at = NOW() WHERE admin_id = $1",
    array($admin['admin_id'])
);
fail_on_db_error($touchResult, 'admin/login: touch last_login_at', $conn);

log_activity($conn, (int) $admin['admin_id'], $admin['login_email'], 'admin_login', null, null,
    "{$admin['login_email']} signed in to the administration portal");

send_success([
    "message" => "Login successful",
    "adminId" => $admin['admin_id'],
    "fullName" => $admin['first_name'] . ' ' . $admin['last_name']
]);

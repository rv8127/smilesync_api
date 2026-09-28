<?php
// admin/get_user.php -- single system_admin row by ?id=, used to prefill
// user-form.html when editing.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';
require_once '../helpers/check_auth.php';

requireAdminAuth();
$id = require_query_param('id');

$conn = getConnection();
$result = pg_query_params($conn,
    "SELECT admin_id, first_name, last_name, login_email, role, account_status
     FROM system_admin WHERE admin_id = $1",
    array($id)
);
fail_on_db_error($result, 'admin/get_user', $conn);
$user = pg_fetch_assoc($result);

if (!$user) {
    send_error(404, "System user not found");
}

send_success(["user" => $user]);

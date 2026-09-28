<?php
// admin/whoami.php -- session check used by every admin page on load, both
// to guard against unauthenticated access (401 -> redirect to index.html)
// and to fill in the sidebar's admin name/email instead of hardcoding them.
require_once '../helpers/headers.php';
require_once '../helpers/check_auth.php';
require_once '../helpers/response.php';

$adminId = requireAdminAuth();

send_success([
    "adminId" => $adminId,
    "fullName" => $_SESSION['admin_name'] ?? '',
    "email" => $_SESSION['admin_email'] ?? ''
]);

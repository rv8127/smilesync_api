<?php
// admin/get_system_announcements.php -- lists ALL system announcements (including expired ones)
// for the System Admin's Announcements page. Patients use announcements/get_system_announcements.php,
// which hides expired ones.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/response.php';
require_once '../helpers/check_auth.php';

requireAdminAuth();

$conn = getConnection();

$result = pg_query($conn,
    "SELECT system_announcement_id, title, message, type, announcement_date, expires_on
     FROM system_announcement
     ORDER BY announcement_date DESC, system_announcement_id DESC"
);
fail_on_db_error($result, 'admin/get_system_announcements', $conn);

send_success(["announcements" => pg_fetch_all($result) ?: []]);

<?php
// announcements/get_system_announcements.php
// SmileSync-wide announcements (maintenance, app updates, etc.) posted by the System Admin.
// Not tied to any clinic -- every patient app sees them alongside their clinic's own announcements.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/response.php';

$conn = getConnection();

$result = pg_query($conn,
    "SELECT system_announcement_id, title, message, type, announcement_date
     FROM system_announcement
     WHERE expires_on IS NULL OR expires_on >= CURRENT_DATE
     ORDER BY announcement_date DESC, system_announcement_id DESC
     LIMIT 5"
);
fail_on_db_error($result, 'announcements/get_system_announcements', $conn);

send_success(["announcements" => pg_fetch_all($result) ?: []]);

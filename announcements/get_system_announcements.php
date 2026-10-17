<?php
// announcements/get_system_announcements.php
// SmileSync-wide announcements (maintenance, app updates, etc.) posted by the System Admin.
// Not tied to any clinic -- every patient app sees them alongside their clinic's own announcements.
// ?audience=web_admin returns the ones meant for the clinic web admin dashboard instead;
// with no audience given it returns the patient-app ones, so the patient app is unchanged.
// Announcements posted for 'both' are included in either list.
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/response.php';

$audience = $_GET['audience'] ?? 'patient_app';
if (!in_array($audience, ['patient_app', 'web_admin'])) {
    send_error(422, "Validation failed", ["audience" => "must be 'patient_app' or 'web_admin'"]);
}

$conn = getConnection();

$result = pg_query_params($conn,
    "SELECT system_announcement_id, title, message, type, announcement_date
     FROM system_announcement
     WHERE (audience = $1 OR audience = 'both') AND (expires_on IS NULL OR expires_on >= CURRENT_DATE)
     ORDER BY announcement_date DESC, system_announcement_id DESC
     LIMIT 5",
    array($audience)
);
fail_on_db_error($result, 'announcements/get_system_announcements', $conn);

send_success(["announcements" => pg_fetch_all($result) ?: []]);

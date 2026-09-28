<?php
// announcements/add_announcement.php
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'clinicCode' => ['required' => true, 'type' => 'string'],
    'title'      => ['required' => true, 'type' => 'string'],
    'date'       => ['required' => true, 'type' => 'date'],
    'type'       => ['type' => 'string'],
    'content'    => ['type' => 'string'],
]);

$conn = getConnection();
$clinic = require_clinic($conn, $data['clinicCode']);

$insertResult = pg_query_params($conn,
    "INSERT INTO announcement (clinic_id, title, message, type, announcement_date)
     VALUES ($1, $2, $3, $4, $5) RETURNING announcement_id",
    array(
        $clinic['clinic_id'],
        $data['title'],
        $data['content'] ?? null,
        $data['type'] ?? 'info',
        $data['date']
    )
);
fail_on_db_error($insertResult, 'announcements/add_announcement: insert', $conn);

$newAnn = pg_fetch_assoc($insertResult);

send_success([
    "message" => "Announcement posted successfully",
    "announcementId" => $newAnn['announcement_id']
]);

<?php
// announcements/delete_announcement.php
require_once '../helpers/headers.php';
require_once '../helpers/db_connect.php';
require_once '../helpers/validate.php';
require_once '../helpers/response.php';

$data = get_json_body();

$data = validate_or_fail($data, [
    'announcementId' => ['required' => true, 'type' => 'int'],
]);

$conn = getConnection();

$result = pg_query_params($conn,
    "DELETE FROM announcement WHERE announcement_id = $1",
    array($data['announcementId'])
);
fail_on_db_error($result, 'announcements/delete_announcement', $conn);

send_success(["message" => "Announcement deleted"]);

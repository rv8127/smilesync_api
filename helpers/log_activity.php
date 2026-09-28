<?php
// helpers/log_activity.php - admin part, uses for log activity for system admin actions.
function log_activity(
    $conn,
    ?int $actorId,
    ?string $actorEmail,
    string $action,
    ?string $targetType,
    ?int $targetId,
    string $description
) {
    $result = pg_query_params($conn,
        "INSERT INTO activity_log (actor_type, actor_id, actor_email, action, target_type, target_id, description)
         VALUES ('system_admin', $1, $2, $3, $4, $5, $6)",
        array($actorId, $actorEmail, $action, $targetType, $targetId, $description)
    );
    if (!$result) {
        error_log("[log_activity] " . pg_last_error($conn));
    }
}
